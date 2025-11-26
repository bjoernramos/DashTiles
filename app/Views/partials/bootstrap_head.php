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
<!-- Material Symbols (Outlined) — bevorzugt lokal, mit CDN-Fallback -->
<!-- Lokale Variante (kopiere die Webfonts ins Repo unter public/assets/vendor/material-symbols/) -->
<link rel="stylesheet" href="<?= esc(base_url('assets/vendor/material-symbols/material-symbols.css')) ?>">
<!-- Fallback von Google Fonts (wird genutzt, wenn lokale Datei nicht vorhanden ist) -->
<link
  rel="stylesheet"
  href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0"
  crossorigin="anonymous"
>
<!-- Material Icons (Legacy) — bevorzugt lokal, mit CDN-Fallback -->
<link rel="stylesheet" href="<?= esc(base_url('assets/vendor/material-icons/material-icons.css')) ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" crossorigin="anonymous">
<meta name="color-scheme" content="light dark" />
<!-- App endpoints for system-wide JS -->
<?php if (isset($pingEnabled)): ?>
<meta name="tp:ping-enabled" content="<?= (int)$pingEnabled ?>">
<?php endif; ?>
<meta name="tp:ping" content="<?= esc(site_url('ping')) ?>">
<meta name="tp:reorder" content="<?= esc(site_url('dashboard/reorder')) ?>">
<!-- System-wide custom CSS -->
<link rel="stylesheet" href="<?= esc(base_url('assets/toolpages.css')) ?>">
<!-- Iconify Runtime (lokal + Fallback) für line-md Icon-Set -->
<script src="<?= esc(base_url('assets/vendor/iconify/iconify.min.js')) ?>" defer></script>
<script src="<?= esc(base_url('assets/vendor/iconify/load-line-md.js')) ?>" defer></script>
<script>
// Falls die lokale Iconify-Runtime nicht vorhanden ist, versuche node_modules, sonst CDN
window.addEventListener('DOMContentLoaded', function () {
  try {
    if (typeof window.Iconify === 'undefined') {
      // Versuch: direkt aus node_modules bedienen (wird via Nginx alias freigegeben)
      var s1 = document.createElement('script');
      s1.src = '/node_modules/@iconify/iconify/dist/iconify.min.js';
      s1.defer = true;
      s1.onerror = function(){
        var s2 = document.createElement('script');
        s2.src = 'https://cdn.jsdelivr.net/npm/@iconify/iconify@3/dist/iconify.min.js';
        s2.defer = true;
        document.head.appendChild(s2);
      };
      document.head.appendChild(s1);
    }
  } catch (e) { /* noop */ }
});
</script>
<!-- Hinweis: Für Multi-Selects wird die native Bootstrap-Multiple-Select verwendet (kein zusätzliches Plugin). -->
