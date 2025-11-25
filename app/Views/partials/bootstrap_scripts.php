<!-- Bootstrap 5 Bundle JS (with Popper) via CDN -->
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
  crossorigin="anonymous"
></script>
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
