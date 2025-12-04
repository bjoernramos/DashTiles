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
      // Helper: find nearest .tp-tile/.tp-tiles
      function nearestTile(el){
        while (el && el !== document) {
          if (el.classList && (el.classList.contains('tp-tile') || el.classList.contains('tp-tiles'))) return el;
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
      var tiles = Array.prototype.slice.call(document.querySelectorAll('.tp-tile, .tp-tiles'));
      if (!tiles.length) return;

      // Only ping tiles that actually show a ping dot and have a target url
      var items = tiles.map(function(t){
        var dot = t.querySelector('.tp-ping');
        var u = t.getAttribute('data-ping-url');
        if (!dot || !u) return null;
        return { el: t, dot: dot, url: u };
      }).filter(Boolean);
      if (!items.length) return;

      // Helper: origin check
      function isSameOrigin(targetUrl){
        try {
          var u = new URL(targetUrl, window.location.href);
          return u.origin === window.location.origin;
        } catch(_) { return false; }
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

        // mark as in‑progress
        setState(it, null, null, 0);

        var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
        var signal = controller ? controller.signal : void 0;
        var timeoutMs = 5000;
        var timeoutId = null;
        if (controller) {
          try { timeoutId = setTimeout(function(){ try { controller.abort(); } catch(_){} }, timeoutMs); } catch(_) {}
        }

        var start = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
        var same = isSameOrigin(it.url);

        // Try HEAD first
        var opts = {
          method: 'HEAD',
          cache: 'no-store',
          redirect: 'follow',
          credentials: same ? 'same-origin' : 'omit',
          mode: same ? 'same-origin' : 'no-cors',
          signal: signal
        };

        function done(ok, status){
          if (timeoutId) { try { clearTimeout(timeoutId); } catch(_) {} }
          var end = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
          var ms = end - start;
          setState(it, ok, Math.round(ms), typeof status === 'number' ? status : 0);
        }

        function fallbackGet(){
          // As a fallback, try GET with no-cors/caching disabled; success resolution == reachable
          var getOpts = {
            method: 'GET',
            cache: 'no-store',
            redirect: 'follow',
            credentials: same ? 'same-origin' : 'omit',
            mode: same ? 'same-origin' : 'no-cors',
            signal: signal
          };
          return fetch(it.url, getOpts).then(function(res){
            if (same) {
              var ok = (res && (res.ok || (res.status>=200 && res.status<400)));
              done(!!ok, res ? res.status : 0);
            } else {
              // Opaque but resolved → treat as reachable
              done(true, 0);
            }
          }).catch(function(){ done(false, 0); });
        }

        return fetch(it.url, opts).then(function(res){
          if (same) {
            // We can read status
            if (!res) { done(false, 0); return; }
            // If HEAD not allowed, retry with GET
            if (res.status === 405 || res.status === 501) {
              return fallbackGet();
            }
            var ok = res.ok || (res.status>=200 && res.status<400);
            done(!!ok, res.status);
          } else {
            // Cross-origin no-cors returns opaque; reaching this .then means network path succeeded
            done(true, 0);
          }
        }).catch(function(){
          // HEAD failed; try GET once
          return fallbackGet();
        });
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
