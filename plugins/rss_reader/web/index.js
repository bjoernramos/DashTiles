// RSS Reader Plugin (client-only MVP)
// Renders a scrollable list of RSS/Atom items from a configurable feed URL.

// NOTE: Vermeidet moderne JS-Features (private Felder, optional chaining),
// um Safari/WebKit ohne Transpile zu unterstützen.
class RssReaderTile {
  constructor(){
    this._container = null;
    this._cfg = null;
    this._ctx = null;
    this._abort = null;
    this._observer = null;
    this._timeoutId = null;
  }

  // Non-blocking render: set up UI and kick off fetching in background when visible
  render(container, cfg, ctx) {
    if (!cfg) cfg = {};
    this._container = container; this._cfg = cfg; this._ctx = ctx;
    try { if (this._abort && typeof this._abort.abort === 'function') this._abort.abort(); } catch(_) {}
    this._abort = new AbortController();
    if (this._observer) { try { this._observer.disconnect(); } catch(_) {} this._observer = null; }
    if (this._timeoutId) { clearTimeout(this._timeoutId); this._timeoutId = null; }

    container.innerHTML = (
      '<div class="rss-root" style="display:flex;flex-direction:column;height:100%;min-height:80px;">'
      + '<div class="rss-header" style="font-weight:600;padding:6px 8px;border-bottom:1px solid rgba(0,0,0,.075)">RSS</div>'
      + '<div class="rss-list" id="rss-list" style="flex:1;overflow:auto;padding:8px;display:flex;flex-direction:column;gap:8px"></div>'
      + '</div>'
    );

    var listEl = container.querySelector('#rss-list');

    var feedUrl = String(cfg.feedUrl || '').trim();
    var maxItems = Math.max(1, Math.min(50, Number((cfg.hasOwnProperty('maxItems') ? cfg.maxItems : 10))));
    var showImages = !!(cfg.hasOwnProperty('showImages') ? cfg.showImages : true);

    if (!feedUrl) {
      listEl.innerHTML = '<div class="text-muted">Bitte einen RSS‑Feed angeben.</div>';
      return;
    }

    // Lightweight skeleton while loading
    listEl.innerHTML = this._skeletonHTML(Math.min(maxItems, 5));

    var self = this;
    function startLoading(){
      // Double-check in case of re-renders
      if (!self._container || (self._abort && self._abort.signal && self._abort.signal.aborted)) return;
      self._doFetchAndRender({ listEl: listEl, feedUrl: feedUrl, maxItems: maxItems, showImages: showImages, cfg: cfg, ctx: ctx }).catch(function(){ /* UI handled inside */ });
    }

    // Defer network work until tile is visible (performance for many tiles)
    try {
      if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function(entries){
          entries.forEach(function(e){
            if (e.isIntersecting) {
              try { obs.disconnect(); } catch(_) {}
              startLoading();
            }
          });
        }, { root: null, rootMargin: '100px', threshold: 0 });
        obs.observe(container);
        this._observer = obs;
        // Fallback: if never intersected (e.g., tiny viewport), start after short delay
        this._timeoutId = setTimeout(function(){ try { obs.disconnect(); } catch(_) {} startLoading(); }, 1200);
      } else {
        // No IO support → start immediately in a microtask
        Promise.resolve().then(startLoading);
      }
    } catch(_) { Promise.resolve().then(startLoading); }
  }

  update(cfg) { this.render(this._container, cfg, this._ctx); }
  dispose() {
    try { if (this._abort && typeof this._abort.abort === 'function') this._abort.abort(); } catch(_) {}
    if (this._observer) { try { this._observer.disconnect(); } catch(_) {} this._observer = null; }
    if (this._timeoutId) { clearTimeout(this._timeoutId); this._timeoutId = null; }
  }

  async _doFetchAndRender(params){
    var listEl = params.listEl, feedUrl = params.feedUrl, maxItems = params.maxItems, showImages = params.showImages, cfg = params.cfg, ctx = params.ctx;
    try {
      var useProxy = (cfg.useProxy !== false); // default true
      var xmlText = '';

      // Implement a request timeout to avoid long hangs
      var controller = this._abort;
      var timeoutMs = Math.max(3000, Math.min(20000, Number((cfg && cfg.timeoutMs != null) ? cfg.timeoutMs : 8000)));
      var timeout = setTimeout(function(){ try { controller.abort(); } catch(_) {} }, timeoutMs);

      if (useProxy && feedUrl.charAt(0) !== '/' && /^https?:\/\//i.test(feedUrl)) {
        var bp = (!ctx || !ctx.basePath || ctx.basePath === '/') ? '' : ctx.basePath;
        var proxyUrl = bp + '/api/plugins/rss_reader/fetch';
        var pres = await fetch(proxyUrl, {
          method: 'POST',
          credentials: 'include',
          signal: controller.signal,
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ url: feedUrl })
        });
        var pdata = null;
        try { pdata = await pres.json(); } catch(_){ pdata = null; }
        if (!pres.ok || !pdata || pdata.ok === false || typeof pdata.xml !== 'string') {
          var msg = (pdata && pdata.error) ? pdata.error : ('HTTP ' + pres.status);
          throw new Error('Proxy error: ' + msg);
        }
        xmlText = String(pdata.xml);
      } else {
        var res = await fetch(feedUrl, {
          signal: controller.signal,
          credentials: 'include',
          headers: { 'Accept': 'application/rss+xml, application/atom+xml, text/xml;q=0.9, */*;q=0.8' }
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        xmlText = await res.text();
      }
      clearTimeout(timeout);

      var parsed = this._parseFeed(xmlText);
      var title = parsed.title; var items = parsed.items;
      try { if (title) { var hdr = this._container ? this._container.querySelector('.rss-header') : null; if (hdr) hdr.textContent = title; } } catch(_){ }
      var limited = items.slice(0, maxItems);
      if (!limited.length) {
        listEl.innerHTML = '<div class="text-muted">Keine Einträge gefunden.</div>';
        return;
      }
      // Korrektes Binding: in obigem Scope existiert kein "self". Auf this binden.
      // Zusätzlich defensiver Fallback, falls _itemHTML unerwartet fehlt.
      if (!this || typeof this._itemHTML !== 'function') {
        listEl.innerHTML = limited.map(function(it){
          var title = (it && it.title) ? String(it.title) : '';
          var href = (it && it.link) ? String(it.link) : '#';
          return '<div class="rss-row">'
            + '<a href="#" data-href="' + (href.replace(/"/g,'&quot;')) + '"'
            + ' style="font-weight:600;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'
            + (title.replace(/[&<>]/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]);}))
            + '</a>'
            + '</div>';
        }).join('');
      } else {
        listEl.innerHTML = limited.map(function(it){ return this._itemHTML(it, showImages, cfg); }.bind(this)).join('');
      }
      var anchors = listEl.querySelectorAll('a[data-href]');
      for (var i=0;i<anchors.length;i++){
        (function(a, selfRef){
          a.addEventListener('click', function(ev){
            ev.preventDefault();
            var url = a.getAttribute('data-href') || '#';
            var beh = (cfg.openBehavior || 'link');
            if (beh === 'modal') {
              var node = document.createElement('div');
              node.innerHTML = '<h5 style="margin-top:0">' + selfRef._escape(a.getAttribute('data-title') || '') + '</h5>' +
                               (a.getAttribute('data-desc') || '');
              if (selfRef._ctx && typeof selfRef._ctx.openModal === 'function') selfRef._ctx.openModal(node, { title: 'Details', width: 720 });
            } else {
              if (selfRef._ctx && typeof selfRef._ctx.openLink === 'function') selfRef._ctx.openLink(url, 'blank');
            }
          });
        })(anchors[i], this);
      }
    } catch (e) {
      var isAbort = e && e.name === 'AbortError';
      var msg = this._escape(isAbort ? 'Zeitüberschreitung' : ((e && e.message) ? e.message : String(e)));
      var hint = (cfg.useProxy === false) ? '<div class="text-muted small">Tipp: Aktiviere "Server‑Proxy verwenden" in der Konfiguration, um CORS‑Probleme zu vermeiden.</div>' : '';
      listEl.innerHTML = '<div class="text-danger">Fehler beim Laden: ' + msg + '</div>' + hint;
    }
  }

  _skeletonHTML(rows){
    var html = '';
    for (var i=0;i<rows;i++){
      html += '<div class="placeholder-glow" style="display:flex;gap:10px;align-items:center">'
           +  '<span class="placeholder" style="width:42px;height:42px;border-radius:6px"></span>'
           +  '<span class="placeholder col-8"></span>'
           +  '</div>';
    }
    return html;
  }

  _itemHTML(it, showImages, cfg){
    var img = (showImages && it.image) ? '<img src="' + this._escape(it.image) + '" alt="" loading="lazy" style="width:42px;height:42px;object-fit:cover;border-radius:6px;flex:0 0 auto">' : '';
    var fdate = it.pubDate ? this._formatDateStr(it.pubDate) : '';
    var date = fdate ? '<div class="text-muted small">' + this._escape(fdate) + '</div>' : '';
    var showDesc = (cfg.showDescription !== false);
    var maxLines = Math.max(1, Math.min(12, Number((cfg && cfg.descriptionMaxLines != null) ? cfg.descriptionMaxLines : 3)));
    var desc = (showDesc && it.description) ? '<div class="text-muted small" style="display:-webkit-box;-webkit-line-clamp:' + maxLines + ';-webkit-box-orient:vertical;overflow:hidden;">' + it.description + '</div>' : '';
    return '<div class="rss-row" style="display:flex;gap:10px;align-items:flex-start;">\n'
      + img + '\n'
      + '<div style="min-width:0;display:flex;flex-direction:column;gap:2px;">\n'
      + '  <a href="#" target="_blank" data-href="' + this._escape(it.link || '#') + '" data-title="' + this._escape(it.title || '') + '" data-desc=' + "'" + this._escapeAttr(it.description || '') + "'" + '\n'
      + '     style="font-weight:600;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + this._escape(it.title || '') + '</a>\n'
      + '  ' + date + '\n'
      + '  ' + desc + '\n'
      + '</div>\n'
      + '</div>';
  }

  _parseFeed(xmlText){
    try {
      var parser = new DOMParser();
      var doc = parser.parseFromString(xmlText, 'application/xml');
      if (doc.getElementsByTagName('parsererror').length) throw new Error('Ungültiges XML');

      // RSS 2.0
      var channel = doc.querySelector('rss > channel');
      if (channel) {
        var title = this._text(channel, 'title');
        var items = Array.prototype.slice.call(channel.getElementsByTagName('item')).map(function(it){ return this._mapRssItem(it); }.bind(this));
        return { title: title, items: items };
      }
      // Atom
      var feed = doc.getElementsByTagName('feed')[0];
      if (feed) {
        var titleNode = feed.getElementsByTagName('title')[0];
        var title2 = titleNode ? (titleNode.textContent || '').trim() : '';
        var entries = Array.prototype.slice.call(feed.getElementsByTagName('entry')).map(function(e){ return this._mapAtomEntry(e); }.bind(this));
        return { title: title2, items: entries };
      }
      return { title: 'RSS', items: [] };
    } catch (e) {
      return { title: 'RSS', items: [] };
    }
  }

  _text(node, tag){
    var n = node.getElementsByTagName(tag)[0];
    return n ? (n.textContent || '').trim() : '';
  }

  _mapRssItem(item){
    var title = this._text(item, 'title');
    var linkNode = item.getElementsByTagName('link')[0];
    var link = linkNode ? (linkNode.textContent || linkNode.getAttribute('href') || '').trim() : '';
    var pubDate = this._text(item, 'pubDate');
    var descNode = item.getElementsByTagName('description')[0];
    var description = descNode ? (descNode.textContent || '').trim() : '';
    var image = '';
    var m1 = item.getElementsByTagName('media:content');
    var m2 = item.getElementsByTagName('media\:content');
    var m3 = item.getElementsByTagNameNS && item.getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'content');
    var mnode = (m1 && m1[0]) || (m2 && m2[0]) || (m3 && m3[0]);
    if (mnode) image = mnode.getAttribute('url') || '';
    if (!image) {
      var enc = item.getElementsByTagName('enclosure')[0];
      if (enc && /^image\//i.test(enc.getAttribute('type') || '')) image = enc.getAttribute('url') || '';
    }
    return { title: title, link: link, pubDate: pubDate, description: description, image: image };
  }

  _mapAtomEntry(entry){
    var titleNode = entry.getElementsByTagName('title')[0];
    var title = titleNode ? (titleNode.textContent || '').trim() : '';
    var link = '';
    var links = Array.prototype.slice.call(entry.getElementsByTagName('link'));
    var alt = null;
    for (var i=0;i<links.length;i++){ if ((links[i].getAttribute('rel') || 'alternate') === 'alternate') { alt = links[i]; break; } }
    if (!alt) alt = links[0];
    if (alt) link = alt.getAttribute('href') || '';
    var updated = entry.getElementsByTagName('updated')[0];
    var pubDate = updated ? (updated.textContent || '').trim() : '';
    var summary = entry.getElementsByTagName('summary')[0] || entry.getElementsByTagName('content')[0];
    var description = summary ? (summary.textContent || '').trim() : '';
    var image = '';
    var m3 = entry.getElementsByTagNameNS && entry.getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'content');
    if (m3 && m3[0]) image = m3[0].getAttribute('url') || '';
    return { title: title, link: link, pubDate: pubDate, description: description, image: image };
  }

  _escape(s){ return String(s).replace(/[&<>]/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]); }); }
  _escapeAttr(s){ return String(s).replace(/[&<>"]/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]); }); }

  _formatDateStr(str){
    try {
      var d = new Date(str);
      if (isNaN(d.getTime())) return str; // fallback: original string
      // Locale: prefer HTML lang, then navigator
      var htmlLang = (typeof document !== 'undefined' && document.documentElement && document.documentElement.lang) ? document.documentElement.lang : '';
      var locale = htmlLang || (typeof navigator !== 'undefined' ? (navigator.language || '') : '') || 'en';
      // Optionen: kompakte Datum+Zeit
      var opts = { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' };
      // Spezielle Formatierung für Deutsch: Monat kurz, 2‑stelliger Tag ergibt z. B. "26. Nov. 2025, 13:47"
      var loc = (locale.toLowerCase().indexOf('de') === 0) ? 'de-DE' : 'en-US';
      return new Intl.DateTimeFormat(loc, opts).format(d);
    } catch(_) {
      return str;
    }
  }
}

export function register(registrar){
  registrar.registerTile('rss.reader.list', RssReaderTile);
}
