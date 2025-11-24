<!-- Bootstrap 5 Bundle JS (with Popper) via CDN -->
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
  crossorigin="anonymous"
></script>
<script>
// Lightweight ping for tiles: marks .tp-ping dot green on success, red on error/timeout.
// Heuristic: fire GET request with mode:no-cors and 3s timeout; if resolved, mark ok; if rejected/timeout, mark err.
(function(){
  const DOT_OK = 'ok', DOT_ERR = 'err';
  function ping(url, timeoutMs){
    return new Promise((resolve, reject) => {
      const ctrl = new AbortController();
      const timer = setTimeout(() => { ctrl.abort(); reject(new Error('timeout')); }, timeoutMs);
      fetch(url, { method: 'GET', mode: 'no-cors', cache: 'no-store', signal: ctrl.signal }).then(() => {
        clearTimeout(timer); resolve(true);
      }).catch(err => { clearTimeout(timer); reject(err); });
    });
  }
  function init(){
    const nodes = document.querySelectorAll('[data-ping-url]');
    nodes.forEach((el, idx) => {
      const url = el.getAttribute('data-ping-url');
      if (!url) return;
      const dot = el.querySelector('.tp-ping');
      if (!dot) return;
      // Stagger requests a bit to avoid bursts
      const delay = 50 * (idx % 20);
      setTimeout(() => {
        ping(url, 3000).then(() => {
          dot.classList.remove(DOT_ERR); dot.classList.add(DOT_OK);
        }).catch(() => {
          dot.classList.remove(DOT_OK); dot.classList.add(DOT_ERR);
        });
      }, delay);
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
</script>
