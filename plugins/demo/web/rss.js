// Demo Plugin: RSS Reader Tile (client-only MVP)
// Fetches and parses RSS/Atom feeds and renders a scrollable list.

class RssReaderTile {
  #container; #cfg; #ctx; #abort;

  async render(container, cfg = {}, ctx) {
    this.#container = container; this.#cfg = cfg; this.#ctx = ctx;
    this.#abort?.abort(); this.#abort = new AbortController();

    // Basic skeleton with scrollable list
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

    try {
      // Note: External feeds may require CORS to be enabled by the source or to be proxied server‑side.
      const res = await fetch(feedUrl, {
        signal: this.#abort.signal,
        credentials: 'include', // cookies if same-origin reverse-proxied
        headers: { 'Accept': 'application/rss+xml, application/atom+xml, text/xml;q=0.9, */*;q=0.8' }
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const text = await res.text();
      const { title, items } = this.#parseFeed(text);
      // Set header title if available
      try {
        if (title) container.querySelector('.rss-header').textContent = title;
      } catch (_) {}
      const limited = items.slice(0, maxItems);
      if (!limited.length) {
        listEl.innerHTML = `<div class="text-muted">Keine Einträge gefunden.</div>`;
        return;
      }
      listEl.innerHTML = limited.map(it => this.#itemHTML(it, showImages)).join('');
      // Make anchor links open self or modal behavior can be handled by onClick per item in future
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
            this.#ctx?.openLink?.(url, 'self');
          }
        });
      });
    } catch (e) {
      listEl.innerHTML = `<div class="text-danger">Fehler beim Laden: ${this.#escape(e.message || String(e))}</div>`;
    }
  }

  update(cfg) { this.render(this.#container, cfg, this.#ctx); }
  dispose() { try { this.#abort?.abort(); } catch(_) {} }

  #itemHTML(it, showImages){
    const img = showImages && it.image ? `<img src="${this.#escape(it.image)}" alt="" style="width:42px;height:42px;object-fit:cover;border-radius:6px;flex:0 0 auto">` : '';
    const date = it.pubDate ? `<div class="text-muted small">${this.#escape(it.pubDate)}</div>` : '';
    const desc = it.description ? `<div class="text-muted small" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">${it.description}</div>` : '';
    return `<div class="rss-row" style="display:flex;gap:10px;align-items:flex-start;">
      ${img}
      <div style="min-width:0;display:flex;flex-direction:column;gap:2px;">
        <a href="#" data-href="${this.#escape(it.link || '#')}" data-title="${this.#escape(it.title || '')}" data-desc='${this.#escapeAttr(it.description || '')}'
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
      // Check parsererror
      if (doc.getElementsByTagName('parsererror').length) throw new Error('Ungültiges XML');

      // Try RSS 2.0
      const channel = doc.querySelector('rss > channel');
      if (channel) {
        const title = this.#text(channel, 'title');
        const items = Array.from(channel.getElementsByTagName('item')).map((it) => this.#mapRssItem(it));
        return { title, items };
      }
      // Try Atom
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
    // description might contain HTML; we will keep as-is (sanitized by host CSP; minimal risks for demo)
    const descNode = item.getElementsByTagName('description')[0];
    const description = descNode ? (descNode.textContent || '').trim() : '';
    // media:content (namespaced)
    let image = '';
    const m1 = item.getElementsByTagName('media:content');
    const m2 = item.getElementsByTagName('media\:content');
    const m3 = item.getElementsByTagNameNS && item.getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'content');
    const mnode = (m1 && m1[0]) || (m2 && m2[0]) || (m3 && m3[0]);
    if (mnode) image = mnode.getAttribute('url') || '';
    // Also check enclosure
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
    // Atom image variants are not standardized; try media:content also
    let image = '';
    const m3 = entry.getElementsByTagNameNS && entry.getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'content');
    if (m3 && m3[0]) image = m3[0].getAttribute('url') || '';
    return { title, link, pubDate, description, image };
  }

  #escape(s){ return String(s).replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }
  #escapeAttr(s){ return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
}

export function register(registrar){
  registrar.registerTile('demo.rss.reader', RssReaderTile);
}
