/* toolpages system-wide JavaScript */
(function(){
  'use strict';

  // Helpers
  function getMeta(name){
    var el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.getAttribute('content') : '';
  }

  // Endpoints from head meta
  var PING_ENDPOINT = getMeta('tp:ping') || '/ping';
  var REORDER_ENDPOINT = getMeta('tp:reorder') || '/dashboard/reorder';

  // --- Ping logic ---------------------------------------------------------
  var DOT_OK = 'ok', DOT_ERR = 'err';
  function doPing(url, timeoutMs){
    return new Promise(function(resolve, reject){
      try {
        var ctrl = new AbortController();
        var timer = setTimeout(function(){ try{ctrl.abort();}catch(_){} reject(new Error('timeout')); }, timeoutMs);
        var qp = new URLSearchParams({ u: url });
        fetch(PING_ENDPOINT + '?' + qp.toString(), {
          method: 'GET',
          cache: 'no-store',
          credentials: 'same-origin',
          signal: ctrl.signal,
          headers: { 'Accept': 'application/json' }
        }).then(function(r){ return r.json().catch(function(){ return {}; }); })
          .then(function(data){ clearTimeout(timer); if (data && data.ok) resolve(true); else reject(new Error('bad')); })
          .catch(function(){ clearTimeout(timer); reject(new Error('fetch')); });
      } catch(e) { reject(e); }
    });
  }

  function runPing(){
    var nodes = document.querySelectorAll('[data-ping-url]');
    nodes.forEach(function(el, idx){
      var url = el.getAttribute('data-ping-url');
      if (!url) return;
      var dot = el.querySelector('.tp-ping');
      if (!dot) return;
      var delay = 50 * (idx % 20);
      setTimeout(function(){
        doPing(url, 3500)
          .then(function(){ dot.classList.remove(DOT_ERR); dot.classList.add(DOT_OK); })
          .catch(function(){ dot.classList.remove(DOT_OK); dot.classList.add(DOT_ERR); });
      }, delay);
    });
  }

  // --- Tile click navigation ---------------------------------------------
  function enableTileClicks(){
    document.querySelectorAll('.tp-tile[data-href]').forEach(function(el){
      if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
      if (!el.hasAttribute('role')) el.setAttribute('role', 'link');
    });
    function isInteractive(target){
      return !!(target && target.closest('a, button, input, select, textarea, label, [data-bs-toggle], .dropdown-menu, .modal, form'));
    }
    document.addEventListener('click', function(ev){
      var target = ev.target;
      var tile = target && target.closest('.tp-tile[data-href]');
      if (!tile) return;
      if (isInteractive(target)) return;
      var href = tile.getAttribute('data-href');
      if (!href) return;
      try { window.open(href, '_blank', 'noopener'); } catch(e) {}
      ev.preventDefault();
    });
    document.addEventListener('keydown', function(ev){
      if (ev.key !== 'Enter' && ev.key !== ' ') return;
      var tile = ev.target && ev.target.closest('.tp-tile[data-href]');
      if (!tile) return;
      var href = tile.getAttribute('data-href');
      if (!href) return;
      try { window.open(href, '_blank', 'noopener'); } catch(e) {}
      ev.preventDefault();
    });
  }

  // --- Drag & Drop reordering --------------------------------------------
  function initSortableTiles(){
    try {
      var containers = document.querySelectorAll('[data-sortable="1"][data-category]');
      if (!containers.length) return;

      var draggingTile = null;
      var draggingCol = null;
      var activeRow = null;

      function getCol(el){ return el ? el.closest('[class*="col-"]') : null; }
      function getRow(el){ return el ? el.closest('[data-sortable="1"][data-category]') : null; }

      function serializeAndSend(row){
        if (!row) return;
        var category = row.getAttribute('data-category') || '';
        var ids = [];
        row.querySelectorAll('.tp-tile[data-tile-id]').forEach(function(tile){
          var id = parseInt(tile.getAttribute('data-tile-id')||'0', 10);
          if (id > 0) ids.push(id);
        });
        if (!ids.length) return;
        var body = new URLSearchParams();
        body.set('category', category);
        ids.forEach(function(id){ body.append('ids[]', String(id)); });
        fetch(REORDER_ENDPOINT, { method: 'POST', credentials: 'same-origin', body: body })
          .catch(function(){ /* ignore */ });
      }

      function onDragStart(e){
        var tile = e.currentTarget;
        draggingTile = tile;
        draggingCol = getCol(tile);
        activeRow = getRow(tile);
        try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', tile.getAttribute('data-tile-id')||''); } catch(_){ }
        tile.classList.add('opacity-50');
      }
      function onDragEnd(e){
        var tile = e.currentTarget;
        tile.classList.remove('opacity-50');
        // After drag ends, persist order of the row where the tile ended up
        var row = getRow(tile) || activeRow;
        serializeAndSend(row);
        draggingTile = null;
        draggingCol = null;
        activeRow = null;
      }
      function onDragOver(e){
        e.preventDefault();
        var overTile = e.target.closest('.tp-tile');
        if (!overTile || !draggingCol) return;
        var overCol = getCol(overTile);
        if (!overCol || overCol === draggingCol) return;
        var row = overCol.parentElement; // .row g-3
        var overRect = overCol.getBoundingClientRect();
        var before = (e.clientY - overRect.top) < (overRect.height / 2);
        if (before) row.insertBefore(draggingCol, overCol); else row.insertBefore(draggingCol, overCol.nextSibling);
      }

      containers.forEach(function(row){
        row.addEventListener('dragover', onDragOver);
        row.addEventListener('drop', function(e){ e.preventDefault(); serializeAndSend(row); });
        row.querySelectorAll('.tp-tile[draggable="true"]').forEach(function(tile){
          tile.addEventListener('dragstart', onDragStart);
          tile.addEventListener('dragend', onDragEnd);
        });
      });
    } catch(e) { /* noop */ }
  }

  // --- Home category collapse state --------------------------------------
  function initHomeCategoryCollapse(){
    try {
      var body = document.body;
      var uid = body && body.getAttribute('data-user-id');
      if (!uid) return; // not logged in or not on home
      var storageKey = 'tp_home_collapsed_' + uid;

      function loadSet(){ try { var raw = localStorage.getItem(storageKey); return new Set(raw ? JSON.parse(raw) : []); } catch(e){ return new Set(); } }
      function saveSet(set){ try { localStorage.setItem(storageKey, JSON.stringify(Array.from(set))); } catch(e){} }

      function setIcon(catId, expanded){
        try {
          var icon = document.querySelector('[data-cat-icon="' + CSS.escape(catId) + '"]');
          if (icon) icon.textContent = expanded ? 'expand_less' : 'expand_more';
        } catch(e) {}
      }

      var collapsed = loadSet();
      document.querySelectorAll('[data-cat-id]').forEach(function(el){
        var id = el.getAttribute('data-cat-id');
        if (!id) return;
        var isCollapsed = collapsed.has(id);
        var isShown = el.classList.contains('show');
        if (isCollapsed && isShown) {
          el.classList.remove('show');
          var btn = document.querySelector('[data-bs-target="#' + CSS.escape(id) + '"]');
          if (btn) btn.setAttribute('aria-expanded', 'false');
        }
        setIcon(id, !isCollapsed);
      });

      document.querySelectorAll('[data-cat-id]').forEach(function(el){
        var id = el.getAttribute('data-cat-id');
        if (!id) return;
        el.addEventListener('hidden.bs.collapse', function(){ collapsed.add(id); saveSet(collapsed); setIcon(id, false); });
        el.addEventListener('shown.bs.collapse', function(){ collapsed.delete(id); saveSet(collapsed); setIcon(id, true); });
      });
    } catch(e) {}
  }

  // --- Add Tile modal: bind submit to active tab -------------------------
  function initAddTileModalSubmitBinding(){
    var btn = document.getElementById('submitAddTile');
    if (!btn) return;
    function setFormByTarget(target){
      switch(target){
        case '#pane-link': btn.setAttribute('form','form-add-link'); break;
        case '#pane-file': btn.setAttribute('form','form-add-file'); break;
        case '#pane-iframe': btn.setAttribute('form','form-add-iframe'); break;
      }
    }
    document.querySelectorAll('#addTileModal [data-bs-toggle="tab"]').forEach(function(tab){
      tab.addEventListener('shown.bs.tab', function(ev){
        var target = ev.target.getAttribute('data-bs-target');
        setFormByTarget(target);
      });
    });
  }

  // --- Init ---------------------------------------------------------------
  function init(){
    runPing();
    setInterval(runPing, 60 * 1000); // every minute
    enableTileClicks();
    initHomeCategoryCollapse();
    initSortableTiles();
    initAddTileModalSubmitBinding();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
