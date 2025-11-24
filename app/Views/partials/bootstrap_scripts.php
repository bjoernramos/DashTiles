<!-- Bootstrap 5 Bundle JS (with Popper) via CDN -->
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
  crossorigin="anonymous"
></script>
<script>
// Tile ping (server-side via /ping) + tile click navigation
(function(){
  const DOT_OK = 'ok', DOT_ERR = 'err';
  const PING_ENDPOINT = '<?= site_url('ping') ?>';
  const REORDER_ENDPOINT = '<?= site_url('dashboard/reorder') ?>';

  function doPing(url, timeoutMs){
    return new Promise((resolve, reject) => {
      try {
        // quick client-side timeout guard
        const ctrl = new AbortController();
        const timer = setTimeout(() => { try{ctrl.abort();}catch(e){} reject(new Error('timeout')); }, timeoutMs);
        const qp = new URLSearchParams({ u: url });
        fetch(PING_ENDPOINT + '?' + qp.toString(), {
          method: 'GET',
          cache: 'no-store',
          credentials: 'same-origin',
          signal: ctrl.signal,
          headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json().catch(() => ({})))
        .then(data => {
          clearTimeout(timer);
          if (data && data.ok) return resolve(true);
          reject(new Error('bad'));
        })
        .catch(() => { clearTimeout(timer); reject(new Error('fetch')); });
      } catch (e) { reject(e); }
    });
  }

  function runPing(){
    const nodes = document.querySelectorAll('[data-ping-url]');
    nodes.forEach((el, idx) => {
      const url = el.getAttribute('data-ping-url');
      if (!url) return;
      const dot = el.querySelector('.tp-ping');
      if (!dot) return;
      const delay = 50 * (idx % 20);
      setTimeout(() => {
        doPing(url, 3500)
          .then(() => { dot.classList.remove(DOT_ERR); dot.classList.add(DOT_OK); })
          .catch(() => { dot.classList.remove(DOT_OK); dot.classList.add(DOT_ERR); });
      }, delay);
    });
  }

  function enableTileClicks(){
    // Make tiles keyboard-focusable
    document.querySelectorAll('.tp-tile[data-href]').forEach(el => {
      if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
      if (!el.hasAttribute('role')) el.setAttribute('role', 'link');
    });

    function isInteractive(target){
      return !!target.closest('a, button, input, select, textarea, label, [data-bs-toggle], .dropdown-menu, .modal, form');
    }

    document.addEventListener('click', function(ev){
      const target = ev.target;
      const tile = target && target.closest('.tp-tile[data-href]');
      if (!tile) return;
      if (isInteractive(target)) return; // don't hijack clicks on controls
      const href = tile.getAttribute('data-href');
      if (!href) return;
      try { window.open(href, '_blank', 'noopener'); } catch(e) { /* ignore */ }
      ev.preventDefault();
    });

    document.addEventListener('keydown', function(ev){
      if (ev.key !== 'Enter' && ev.key !== ' ') return;
      const tile = ev.target && ev.target.closest('.tp-tile[data-href]');
      if (!tile) return;
      const href = tile.getAttribute('data-href');
      if (!href) return;
      try { window.open(href, '_blank', 'noopener'); } catch(e) { /* ignore */ }
      ev.preventDefault();
    });
  }

  // Drag & Drop Sortierung innerhalb einer Kategorie im Dashboard
  function initSortableTiles(){
    try {
      const containers = document.querySelectorAll('[data-sortable="1"][data-category]');
      if (!containers.length) return;

      let draggingTile = null;
      let draggingCol = null;

      function getCol(el){
        if (!el) return null;
        // die umschließende Grid-Spalte verschieben
        return el.closest('[class*="col-"]');
      }

      function onDragStart(e){
        const tile = e.currentTarget;
        draggingTile = tile;
        draggingCol = getCol(tile);
        try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', tile.getAttribute('data-tile-id')||''); } catch(_){}
        tile.classList.add('opacity-50');
      }
      function onDragEnd(e){
        const tile = e.currentTarget;
        tile.classList.remove('opacity-50');
        draggingTile = null;
        draggingCol = null;
      }
      function onDragOver(e){
        e.preventDefault(); // erlaub Drop
        const overTile = e.target.closest('.tp-tile');
        if (!overTile || !draggingCol) return;
        const overCol = getCol(overTile);
        if (!overCol || overCol === draggingCol) return;
        const row = overCol.parentElement; // .row g-3
        // Einfügeposition bestimmen: vor oder nach overCol basierend auf Cursor
        const overRect = overCol.getBoundingClientRect();
        const before = (e.clientY - overRect.top) < (overRect.height / 2);
        if (before) {
          row.insertBefore(draggingCol, overCol);
        } else {
          row.insertBefore(draggingCol, overCol.nextSibling);
        }
      }
      function serializeAndSend(row){
        const category = row.getAttribute('data-category') || '';
        const ids = [];
        row.querySelectorAll('.tp-tile[data-tile-id]').forEach(function(tile){
          const id = parseInt(tile.getAttribute('data-tile-id')||'0', 10);
          if (id > 0) ids.push(id);
        });
        if (!ids.length) return;
        const body = new URLSearchParams();
        body.set('category', category);
        ids.forEach(id => body.append('ids[]', String(id)));
        fetch(REORDER_ENDPOINT, { method: 'POST', credentials: 'same-origin', body })
          .then(() => { /* ok */ })
          .catch(() => { /* ignore */ });
      }

      containers.forEach(function(row){
        row.addEventListener('dragover', onDragOver);
        // Drop am Container triggert Speichern
        row.addEventListener('drop', function(e){ e.preventDefault(); serializeAndSend(row); });
        row.querySelectorAll('.tp-tile[draggable="true"]').forEach(function(tile){
          tile.addEventListener('dragstart', onDragStart);
          tile.addEventListener('dragend', onDragEnd);
        });
      });
    } catch(e) { /* noop */ }
  }

  // Home: persist collapsed/expanded category state per user (localStorage)
  function initHomeCategoryCollapse(){
    try {
      const body = document.body;
      const uid = body && body.getAttribute('data-user-id');
      if (!uid) return; // not logged in or not on home
      const storageKey = 'tp_home_collapsed_' + uid;

      function loadSet(){
        try { const raw = localStorage.getItem(storageKey); return new Set(raw ? JSON.parse(raw) : []); }
        catch(e){ return new Set(); }
      }
      function saveSet(set){
        try { localStorage.setItem(storageKey, JSON.stringify(Array.from(set))); } catch(e){}
      }

      function setIcon(catId, expanded){
        try {
          const icon = document.querySelector('[data-cat-icon="' + CSS.escape(catId) + '"]');
          if (icon) {
            icon.textContent = expanded ? 'expand_less' : 'expand_more';
          }
        } catch(e) { /* noop */ }
      }

      const collapsed = loadSet();
      // Apply stored state before user interacts
      document.querySelectorAll('[data-cat-id]').forEach(function(el){
        const id = el.getAttribute('data-cat-id');
        if (!id) return;
        const isCollapsed = collapsed.has(id);
        const isShown = el.classList.contains('show');
        if (isCollapsed && isShown) {
          // remove show class to start collapsed
          el.classList.remove('show');
          // Update aria-expanded on toggler, if present
          const btn = document.querySelector('[data-bs-target="#' + CSS.escape(id) + '"]');
          if (btn) btn.setAttribute('aria-expanded', 'false');
        }
        // Update icon on load
        setIcon(id, !isCollapsed);
      });

      // Listen to collapse events to persist changes
      document.querySelectorAll('[data-cat-id]').forEach(function(el){
        const id = el.getAttribute('data-cat-id');
        if (!id) return;
        el.addEventListener('hidden.bs.collapse', function(){
          collapsed.add(id); saveSet(collapsed);
          setIcon(id, false);
        });
        el.addEventListener('shown.bs.collapse', function(){
          collapsed.delete(id); saveSet(collapsed);
          setIcon(id, true);
        });
      });
    } catch(e) { /* no-op */ }
  }

  function init(){
    runPing();
    // Repeat every 60 seconds
    setInterval(runPing, 60 * 1000);
    enableTileClicks();
    initHomeCategoryCollapse();
    initSortableTiles();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
</script>
