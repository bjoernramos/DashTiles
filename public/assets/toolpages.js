// toolpages – lightweight shared frontend helpers
(function(){
  'use strict';

  // --- Header Search ------------------------------------------------------
  function engineUrl(engine, query){
    var q = encodeURIComponent(query || '');
    switch ((engine || 'google')) {
      case 'duckduckgo': return 'https://duckduckgo.com/?q=' + q;
      case 'bing':       return 'https://www.bing.com/search?q=' + q;
      case 'startpage':  return 'https://www.startpage.com/search?query=' + q; // modern endpoint
      case 'ecosia':     return 'https://www.ecosia.org/search?q=' + q;
      case 'google':
      default:           return 'https://www.google.com/search?q=' + q;
    }
  }

  function initHeaderSearch(){
    try {
      var form = document.querySelector('form[data-tp-search="1"]');
      if (!form) return;
      var input = form.querySelector('input[type="search"]');
      var btn = form.querySelector('button[type="submit"]');
      var engine = form.getAttribute('data-engine') || 'google';
      var autofocus = form.getAttribute('data-autofocus') === '1';

      function openSearch(){
        if (!input) return;
        var q = (input.value || '').trim();
        if (q === '') return; // Require a query
        var url = engineUrl(engine, q);
        try { window.open(url, '_blank', 'noopener,noreferrer'); } catch(_) {}
      }

      if (autofocus && input) {
        try { setTimeout(function(){ input.focus(); input.select && input.select(); }, 50); } catch(_) {}
      }

      form.addEventListener('submit', function(ev){ ev.preventDefault(); openSearch(); });
      if (btn) btn.addEventListener('click', function(ev){ ev.preventDefault(); openSearch(); });
      if (input) input.addEventListener('keydown', function(ev){ if (ev.key === 'Enter') { ev.preventDefault(); openSearch(); } });
    } catch(e) { /* noop */ }
  }

  // --- Clock --------------------------------------------------------------
  function startTime(){
    try {
      var clockEl = document.getElementById('clock');
      if (!clockEl) return;
      function pad(n){ return (n<10?'0':'') + n; }
      function tick(){
        var now = new Date();
        var str = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate())
          + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        clockEl.textContent = str;
      }
      tick();
      setInterval(tick, 1000);
    } catch(e) { /* noop */ }
  }

  // --- Tile click handler -------------------------------------------------
  function initTileClicks(){
    try {
      // Helper: find nearest .tp-tile
      function nearestTile(el){
        while (el && el !== document) {
          if (el.classList && el.classList.contains('tp-tile')) return el;
          el = el.parentElement;
        }
        return null;
      }

      // Ignore clicks that originate from interactive controls inside the tile
      function isInteractiveTarget(target){
        if (!target) return false;
        var tag = (target.tagName || '').toLowerCase();
        if (['a','button','input','select','textarea','label','summary','details'].indexOf(tag) !== -1) return true;
        // Anything already marked as button-like
        if (target.closest && target.closest('.btn, [role="button"], [data-bs-toggle], .dropdown-menu, .modal')) return true;
        return false;
      }

      function openTile(tile, evt){
        if (!tile || !tile.getAttribute) return;
        var href = tile.getAttribute('data-href');
        if (!href) return;
        try { window.open(href, '_blank', 'noopener,noreferrer'); } catch(_) {}
      }

      // Left-click
      document.addEventListener('click', function(ev){
        var tile = nearestTile(ev.target);
        if (!tile) return;
        if (!tile.hasAttribute('data-href')) return;
        if (isInteractiveTarget(ev.target)) return; // allow native behavior for controls
        // Allow users to select text without opening; only act on primary button
        if (ev.button !== 0) return;
        // Prevent default only if the target isn't an anchor
        ev.preventDefault();
        openTile(tile, ev);
      });

      // Middle-click (auxclick)
      document.addEventListener('auxclick', function(ev){
        if (ev.button !== 1) return;
        var tile = nearestTile(ev.target);
        if (!tile) return;
        if (!tile.hasAttribute('data-href')) return;
        if (isInteractiveTarget(ev.target)) return;
        ev.preventDefault();
        openTile(tile, ev);
      });
    } catch(e) { /* noop */ }
  }

  // --- Init on ready ------------------------------------------------------
  function onReady(fn){
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      setTimeout(fn, 0);
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  // --- Tile pinger --------------------------------------------------------
  function initTilePing(){
    try {
      var tiles = Array.prototype.slice.call(document.querySelectorAll('.tp-tile'));
      if (!tiles.length) return;

      // Only ping tiles that actually show a ping dot and have a target url
      var items = tiles.map(function(t){
        var dot = t.querySelector('.tp-ping');
        var u = t.getAttribute('data-ping-url');
        if (!dot || !u) return null;
        return { el: t, dot: dot, url: u };
      }).filter(Boolean);
      if (!items.length) return;

      // Resolve base for endpoint respecting <base href> and reverse proxy base path
      function buildPingEndpoint(targetUrl){
        try {
          // Prefer explicit endpoint from <meta name="tp:ping" content="...">
          var meta = document.querySelector('meta[name="tp:ping"][content]');
          if (meta) {
            var href = meta.getAttribute('content');
            // Ensure we append the u parameter correctly
            var u = new URL(href, window.location.origin);
            u.searchParams.set('u', targetUrl);
            return u.toString();
          }

          // Fallback: derive from <base href> respecting reverse proxy base path
          var baseEl = document.querySelector('base[href]');
          var baseHref = baseEl ? baseEl.getAttribute('href') : null;
          var root = baseHref ? new URL(baseHref, window.location.origin) : new URL('/', window.location.origin);
          var ep = new URL('ping', root);
          ep.searchParams.set('u', targetUrl);
          return ep.toString();
        } catch(_) {
          // Last resort
          return '/ping?u=' + encodeURIComponent(targetUrl);
        }
      }

      var visible = true;
      try {
        document.addEventListener('visibilitychange', function(){ visible = !document.hidden; });
      } catch(_) {}

      function setState(it, ok, ms, status){
        try {
          var d = it.dot;
          d.classList.remove('tp-ping--ok','tp-ping--bad','tp-ping--unknown');
          if (ok === true) d.classList.add('tp-ping--ok');
          else if (ok === false) d.classList.add('tp-ping--bad');
          else d.classList.add('tp-ping--unknown');
          if (typeof ms === 'number') d.setAttribute('data-ms', String(ms));
          if (typeof status === 'number') d.setAttribute('data-status', String(status));
          // Optional quick tooltip for debugging
          var parts = [];
          if (typeof status === 'number') parts.push('HTTP ' + status);
          if (typeof ms === 'number') parts.push(ms + ' ms');
          if (parts.length) d.title = parts.join(' · ');
        } catch(_) {}
      }

      function pingOne(it){
        if (typeof navigator !== 'undefined' && 'onLine' in navigator && navigator.onLine === false) {
          // offline: mark unknown and skip
          setState(it, null, null, 0);
          return Promise.resolve();
        }
        var endpoint = buildPingEndpoint(it.url);
        // mark as in‑progress
        setState(it, null, null, 0);
        return fetch(endpoint, { method: 'GET', credentials: 'same-origin' })
          .then(function(r){ return r.json().catch(function(){ return { ok:false, status:r.status||0, ms:null }; }); })
          .then(function(data){ setState(it, !!data.ok, (typeof data.ms==='number'?data.ms:null), (typeof data.status==='number'?data.status:0)); })
          .catch(function(){ setState(it, false, null, 0); });
      }

      // Stagger initial pings to avoid burst
      items.forEach(function(it, idx){
        setTimeout(function(){ pingOne(it); }, 150 * idx);
      });

      // Periodic pings (default 60s). Pause when tab not visible.
      var intervalMs = 60000;
      setInterval(function(){
        if (!visible) return;
        items.forEach(function(it, idx){ setTimeout(function(){ pingOne(it); }, 100 * idx); });
      }, intervalMs);
    } catch(e) { /* noop */ }
  }

  onReady(function(){
    initHeaderSearch();
    if (document.getElementById('clock')) { startTime(); }
    initTileClicks();
    initTilePing();
  });

})();
