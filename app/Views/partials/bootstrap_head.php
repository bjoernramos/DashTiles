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
<?php
  // Expose effective base path to frontend JS so assets resolve under subpaths (e.g., /toolpages)
  $tpBasePath = rtrim((string) (getenv('toolpages.basePath') ?: '/'), '/');
  if ($tpBasePath === '') { $tpBasePath = '/'; }
?>
<script>
// Publish base path for asset loaders (used by Iconify line-md loader, etc.)
(function(){
  try {
    var bp = '<?= esc($tpBasePath) ?>';
    window.__TP_BASE_PATH__ = bp;
    window.__TP_ASSETS_BASE__ = bp === '/' ? '' : bp; // used to prefix "/assets/..." when app runs under subpath
  } catch(e) { /* noop */ }
})();
</script>
<!-- Bootstrap 5 CSS (lokal, um CDN-Latenzen zu vermeiden) -->
<link
  href="<?= esc(base_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>"
  rel="stylesheet"
/>
<!-- Material Symbols (Outlined) — bevorzugt lokal, mit CDN-Fallback -->
<!-- Lokale Variante (kopiere die Webfonts ins Repo unter public/assets/vendor/material-symbols/) -->
<link rel="stylesheet" href="<?= esc(base_url('assets/vendor/material-symbols/material-symbols.css')) ?>">
<!-- Entfernt: Externer Google Fonts Fallback, um externe Latenzen zu vermeiden -->
<!-- Material Icons (Legacy) — bevorzugt lokal -->
<link rel="stylesheet" href="<?= esc(base_url('assets/vendor/material-icons/material-icons.css')) ?>">
<!-- Entfernt: Externer Google Fonts Fallback für Material Icons -->
<meta name="color-scheme" content="light dark" />
<!-- App endpoints for system-wide JS -->
<?php if (isset($pingEnabled)): ?>
<meta name="tp:ping-enabled" content="<?= (int)$pingEnabled ?>">
<?php endif; ?>
<meta name="tp:ping" content="<?= esc(site_url('ping')) ?>">
<meta name="tp:reorder" content="<?= esc(site_url('dashboard/reorder')) ?>">
<!-- System-wide custom CSS -->
<link rel="stylesheet" href="<?= esc(base_url('assets/toolpages.css')) ?>">
<!-- Iconify Runtime (nur lokal, ohne Fallbacks) für line-md Icon-Set -->
<script src="<?= esc(base_url('assets/vendor/iconify/iconify.min.js')) ?>" defer></script>
<script src="<?= esc(base_url('assets/vendor/iconify/load-line-md.js')) ?>" defer></script>
<!-- Hinweis: Für Multi-Selects wird die native Bootstrap-Multiple-Select verwendet (kein zusätzliches Plugin). -->
