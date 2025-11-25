<!-- Bootstrap 5 Bundle JS (lokal) → primär aus public/assets/vendor, Fallback auf /node_modules -->
<script>
(function(){
  try {
    var s = document.createElement('script');
    s.src = '<?= esc(base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>';
    s.onerror = function(){
      var f = document.createElement('script');
      f.src = '/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js';
      document.body.appendChild(f);
    };
    document.body.appendChild(s);
  } catch(e) { /* noop */ }
})();
</script>
<!-- System-wide custom JS -->
<script src="<?= esc(base_url('assets/toolpages.js')) ?>"></script>

<!-- Shared hidden form (alternative approach / fallback) -->
<form id="tp-shared-form" method="post" action="#" class="d-none" aria-hidden="true">
  <input type="hidden" name="_method" value="post">
  <!-- Additional hidden inputs can be injected dynamically if needed -->
  <button type="submit" hidden></button>
  <noscript>
    <!-- If JavaScript is disabled, this form remains unused. Individual pages should provide standard forms. -->
  </noscript>
  <script>
    // Helper to programmatically submit the shared form
    window.tpSubmitSharedForm = function(actionUrl, params){
      try {
        var form = document.getElementById('tp-shared-form');
        if (!form) return;
        form.setAttribute('action', actionUrl || '#');
        // Cleanup previous dynamic inputs
        Array.from(form.querySelectorAll('[data-tp-dyn="1"]')).forEach(function(n){ n.remove(); });
        if (params && typeof params === 'object'){
          Object.keys(params).forEach(function(key){
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = String(params[key]);
            input.setAttribute('data-tp-dyn','1');
            form.appendChild(input);
          });
        }
        form.submit();
      } catch(e) { /* noop */ }
    };
  </script>
</form>
