// RSS Reader Plugin (client-only MVP)
// Renders a scrollable list of RSS/Atom items from a configurable feed URL.

class RssReaderTile {
  #container; #cfg; #ctx; #abort; #observer; #timeoutId;

  // Non-blocking render: set up UI and kick off fetching in background when visible
  render(container, cfg = {}, ctx) {
    this.#container = container; this.#cfg = cfg; this.#ctx = ctx;
    this.#abort?.abort(); this.#abort = new AbortController();
    if (this.#observer) { try { this.#observer.disconnect(); } catch(_) {} this.#observer = null; }
    if (this.#timeoutId) { clearTimeout(this.#timeoutId); this.#timeoutId = null; }

    container.innerHTML = `
      <div class="rss-root" style="display:flex;flex-direction:column;height:100%;min-height:80px;">
        <div class="rss-header" style="font-weight:600;padding:6px 8px;border-bottom:1px solid rgba(0,0,0,.075)">RSS</div>
        <div class="rss-list" id="rss-list" style="flex:1;overflow:auto;padding:8px;display:flex;flex-direction:column;gap:8px"></div>
      </div>`;

    const listEl = container.querySelector('#rss-list');

    const feedUrl = String(cfg.feedUrl || '').trim();
    const maxItems = Math.max(1, Math.min(50, Number(cfg.maxItems ?? 10)));
    const showImages = !!(cfg.showImages ?? true);

    if (!feedUrl) {
      listEl.innerHTML = `<div class="text-muted">Bitte einen RSS‑Feed angeben.</div>`;
      return;
    }

    // Lightweight skeleton while loading
    listEl.innerHTML = this.#skeletonHTML(Math.min(maxItems, 5));

    const startLoading = () => {
      // Double-check in case of re-renders
      if (!this.#container || this.#abort.signal.aborted) return;
      this.#doFetchAndRender({ listEl, feedUrl, maxItems, showImages, cfg, ctx }).catch(() => {/* UI handled inside */});
    };

    // Defer network work until tile is visible (performance for many tiles)
    try {
      if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver((entries) => {
          entries.forEach((e) => {
            if (e.isIntersecting) {
              try { obs.disconnect(); } catch(_) {}
              startLoading();
            }
          });
        }, { root: null, rootMargin: '100px', threshold: 0 });
        obs.observe(container);
        this.#observer = obs;
        // Fallback: if never intersected (e.g., tiny viewport), start after short delay
        this.#timeoutId = setTimeout(() => { try { obs.disconnect(); } catch(_) {} startLoading(); }, 1200);
      } else {
        // No IO support → start immediately in a microtask
        Promise.resolve().then(startLoading);
      }
    } catch(_) { Promise.resolve().then(startLoading); }
  }

  update(cfg) { this.render(this.#container, cfg, this.#ctx); }
  dispose() {
    try { this.#abort?.abort(); } catch(_) {}
    if (this.#observer) { try { this.#observer.disconnect(); } catch(_) {} this.#observer = null; }
    if (this.#timeoutId) { clearTimeout(this.#timeoutId); this.#timeoutId = null; }
  }

  async #doFetchAndRender({ listEl, feedUrl, maxItems, showImages, cfg, ctx }){
    try {
      const useProxy = (cfg.useProxy !== false); // default true
      let xmlText = '';

      // Implement a request timeout to avoid long hangs
      const controller = this.#abort;
      const timeoutMs = Math.max(3000, Math.min(20000, Number(cfg.timeoutMs || 8000)));
      const timeout = setTimeout(() => { try { controller.abort(); } catch(_) {} }, timeoutMs);

      if (useProxy && !feedUrl.startsWith('/') && /^https?:\/\//i.test(feedUrl)) {
        const bp = (!ctx || !ctx.basePath || ctx.basePath === '/') ? '' : ctx.basePath;
        const proxyUrl = bp + '/api/plugins/rss_reader/fetch';
        const pres = await fetch(proxyUrl, {
          method: 'POST',
          credentials: 'include',
          signal: controller.signal,
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ url: feedUrl })
        });
        const pdata = await pres.json().catch(() => null);
        if (!pres.ok || !pdata || pdata.ok === false || typeof pdata.xml !== 'string') {
          const msg = (pdata && pdata.error) ? pdata.error : ('HTTP ' + pres.status);
          throw new Error('Proxy error: ' + msg);
        }
        xmlText = String(pdata.xml);
      } else {
        const res = await fetch(feedUrl, {
          signal: controller.signal,
          credentials: 'include',
          headers: { 'Accept': 'application/rss+xml, application/atom+xml, text/xml;q=0.9, */*;q=0.8' }
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        xmlText = await res.text();
      }
      clearTimeout(timeout);

      const { title, items } = this.#parseFeed(xmlText);
      try { if (title) this.#container.querySelector('.rss-header').textContent = title; } catch(_){ }
      const limited = items.slice(0, maxItems);
      if (!limited.length) {
        listEl.innerHTML = `<div class="text-muted">Keine Einträge gefunden.</div>`;
        return;
      }
      listEl.innerHTML = limited.map(it => this.#itemHTML(it, showImages, cfg)).join('');
      listEl.querySelectorAll('a[data-href]').forEach(a => {
        a.addEventListener('click', (ev) => {
          ev.preventDefault();
          const url = a.getAttribute('data-href') || '#';
          const beh = (cfg.openBehavior || 'link');
          if (beh === 'modal') {
            const node = document.createElement('div');
            node.innerHTML = `<h5 style="margin-top:0">${this.#escape(a.getAttribute('data-title') || '')}</h5>` +
                             (a.getAttribute('data-desc') || '');
            this.#ctx?.openModal?.(node, { title: 'Details', width: 720 });
          } else {
            this.#ctx?.openLink?.(url, 'blank');
          }
        });
      });
    } catch (e) {
      const msg = this.#escape(e?.name === 'AbortError' ? 'Zeitüberschreitung' : (e?.message || String(e)));
      const hint = (cfg.useProxy === false) ? '<div class="text-muted small">Tipp: Aktiviere "Server‑Proxy verwenden" in der Konfiguration, um CORS‑Probleme zu vermeiden.</div>' : '';
      listEl.innerHTML = `<div class="text-danger">Fehler beim Laden: ${msg}</div>${hint}`;
    }
  }

  #itemHTML(it, showImages, cfg){
    const img = showImages && it.image ? `<img src="${this.#escape(it.image)}" alt="" loading="lazy" style="width:42px;height:42px;object-fit:cover;border-radius:6px;flex:0 0 auto">` : '';
    const fdate = it.pubDate ? this.#formatDateStr(it.pubDate) : '';
    const date = fdate ? `<div class="text-muted small">${this.#escape(fdate)}</div>` : '';
    const showDesc = (cfg.showDescription !== false);
    const maxLines = Math.max(1, Math.min(12, Number(cfg.descriptionMaxLines ?? 3)));
    const desc = (showDesc && it.description) ? `<div class="text-muted small" style="display:-webkit-box;-webkit-line-clamp:${maxLines};-webkit-box-orient:vertical;overflow:hidden;">${it.description}</div>` : '';
    return `<div class="rss-row" style="display:flex;gap:10px;align-items:flex-start;">
      ${img}
      <div style="min-width:0;display:flex;flex-direction:column;gap:2px;">
        <a href="#" target="_blank" data-href="${this.#escape(it.link || '#')}" data-title="${this.#escape(it.title || '')}" data-desc='${this.#escapeAttr(it.description || '')}'
           style="font-weight:600;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${this.#escape(it.title || '')}</a>
        ${date}
        ${desc}
      </div>
    </div>`;
  }

  #parseFeed(xmlText){
    try {
      const parser = new DOMParser();
      const doc = parser.parseFromString(xmlText, 'application/xml');
      if (doc.getElementsByTagName('parsererror').length) throw new Error('Ungültiges XML');

      // RSS 2.0
      const channel = doc.querySelector('rss > channel');
      if (channel) {
        const title = this.#text(channel, 'title');
        const items = Array.from(channel.getElementsByTagName('item')).map((it) => this.#mapRssItem(it));
        return { title, items };
      }
      // Atom
      const feed = doc.getElementsByTagName('feed')[0];
      if (feed) {
        const titleNode = feed.getElementsByTagName('title')[0];
        const title = titleNode ? (titleNode.textContent || '').trim() : '';
        const entries = Array.from(feed.getElementsByTagName('entry')).map((e) => this.#mapAtomEntry(e));
        return { title, items: entries };
      }
      return { title: 'RSS', items: [] };
    } catch (e) {
      return { title: 'RSS', items: [] };
    }
  }

  #text(node, tag){
    const n = node.getElementsByTagName(tag)[0];
    return n ? (n.textContent || '').trim() : '';
  }

  #mapRssItem(item){
    const title = this.#text(item, 'title');
    const linkNode = item.getElementsByTagName('link')[0];
    const link = linkNode ? (linkNode.textContent || linkNode.getAttribute('href') || '').trim() : '';
    const pubDate = this.#text(item, 'pubDate');
    const descNode = item.getElementsByTagName('description')[0];
    const description = descNode ? (descNode.textContent || '').trim() : '';
    let image = '';
    const m1 = item.getElementsByTagName('media:content');
    const m2 = item.getElementsByTagName('media\:content');
    const m3 = item.getElementsByTagNameNS && item.getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'content');
    const mnode = (m1 && m1[0]) || (m2 && m2[0]) || (m3 && m3[0]);
    if (mnode) image = mnode.getAttribute('url') || '';
    if (!image) {
      const enc = item.getElementsByTagName('enclosure')[0];
      if (enc && /^image\//i.test(enc.getAttribute('type') || '')) image = enc.getAttribute('url') || '';
    }
    return { title, link, pubDate, description, image };
  }

  #mapAtomEntry(entry){
    const titleNode = entry.getElementsByTagName('title')[0];
    const title = titleNode ? (titleNode.textContent || '').trim() : '';
    let link = '';
    const links = Array.from(entry.getElementsByTagName('link'));
    const alt = links.find(l => (l.getAttribute('rel') || 'alternate') === 'alternate') || links[0];
    if (alt) link = alt.getAttribute('href') || '';
    const updated = entry.getElementsByTagName('updated')[0];
    const pubDate = updated ? (updated.textContent || '').trim() : '';
    const summary = entry.getElementsByTagName('summary')[0] || entry.getElementsByTagName('content')[0];
    const description = summary ? (summary.textContent || '').trim() : '';
    let image = '';
    const m3 = entry.getElementsByTagNameNS && entry.getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'content');
    if (m3 && m3[0]) image = m3[0].getAttribute('url') || '';
    return { title, link, pubDate, description, image };
  }

  #escape(s){ return String(s).replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }
  #escapeAttr(s){ return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

  #formatDateStr(str){
    try {
      const d = new Date(str);
      if (isNaN(d.getTime())) return str; // fallback: original string
      // Locale: prefer HTML lang, then navigator
      const htmlLang = (typeof document !== 'undefined' && document.documentElement && document.documentElement.lang) ? document.documentElement.lang : '';
      const locale = htmlLang || (typeof navigator !== 'undefined' ? (navigator.language || '') : '') || 'en';
      // Optionen: kompakte Datum+Zeit
      const opts = { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' };
      // Spezielle Formatierung für Deutsch: Monat kurz, 2‑stelliger Tag ergibt z. B. "26. Nov. 2025, 13:47"
      const loc = locale.toLowerCase().startsWith('de') ? 'de-DE' : 'en-US';
      return new Intl.DateTimeFormat(loc, opts).format(d);
    } catch(_) {
      return str;
    }
  }
}

export function register(registrar){
  registrar.registerTile('rss.reader.list', RssReaderTile);
}
