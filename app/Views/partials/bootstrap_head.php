<!-- System Dark Mode bootstrapper: sets Bootstrap color mode before paint -->
<script>
// Set Bootstrap v5.3 color mode from system preference (no local override yet)
(function(){
  try {
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = prefersDark ? 'dark' : 'light';
    const de = document.documentElement;
    if (de.getAttribute('data-bs-theme') !== theme) {
      de.setAttribute('data-bs-theme', theme);
    }
    // Listen for changes
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(ev){
        document.documentElement.setAttribute('data-bs-theme', ev.matches ? 'dark' : 'light');
      });
    }
  } catch(e) { /* noop */ }
})();
</script>
<!-- Bootstrap 5 CSS via CDN -->
<link
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
  rel="stylesheet"
  integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
  crossorigin="anonymous"
/>
<!-- Google Material Symbols (Outlined) for iconography -->
<link
  rel="stylesheet"
  href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0"
  crossorigin="anonymous"
>
<meta name="color-scheme" content="light dark" />
<!-- App endpoints for system-wide JS -->
<meta name="tp:ping" content="<?= esc(site_url('ping')) ?>">
<meta name="tp:reorder" content="<?= esc(site_url('dashboard/reorder')) ?>">
<!-- System-wide custom CSS -->
<link rel="stylesheet" href="<?= esc(base_url('assets/toolpages.css')) ?>">
<!-- Hinweis: Für Multi-Selects wird die native Bootstrap-Multiple-Select verwendet (kein zusätzliches Plugin). -->
