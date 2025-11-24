<!-- Bootstrap 5 Bundle JS (with Popper) via CDN -->
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
  crossorigin="anonymous"
></script>
<script>
// Tile ping + tile click navigation
(function(){
  const DOT_OK = 'ok', DOT_ERR = 'err';

  function doFetch(url, timeoutMs){
    return new Promise((resolve, reject) => {
      try {
        const u = new URL(url, location.href);
        const sameOrigin = u.origin === location.origin;
        const ctrl = new AbortController();
        const timer = setTimeout(() => { try{ctrl.abort();}catch(e){} reject(new Error('timeout')); }, timeoutMs);
        const opts = {
          method: sameOrigin ? 'HEAD' : 'GET',
          mode: sameOrigin ? 'same-origin' : 'no-cors',
          cache: 'no-store',
          credentials: sameOrigin ? 'same-origin' : 'omit',
          redirect: 'follow',
          referrerPolicy: 'no-referrer',
          signal: ctrl.signal,
        };
        fetch(u.href, opts).then(() => { clearTimeout(timer); resolve(true); }).catch(() => { clearTimeout(timer); reject(new Error('fetch')); });
      } catch (e) {
        // Invalid URL
        reject(e);
      }
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
        doFetch(url, 3000)
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

  function init(){
    runPing();
    // Repeat every 60 seconds
    setInterval(runPing, 60 * 1000);
    enableTileClicks();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
</script>
