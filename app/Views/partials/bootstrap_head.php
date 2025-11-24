<!-- Bootstrap 5 CSS via CDN -->
<link
  href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
  rel="stylesheet"
  integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
  crossorigin="anonymous"
/>
<!-- Hinweis: Für Multi-Selects wird die native Bootstrap-Multiple-Select verwendet (kein zusätzliches Plugin). -->
<style>
/* Global helpers for tile ping indicator */
.tp-tile { position: relative; }
.tp-tile[data-href] { cursor: pointer; }
.tp-ping { position: absolute; right: 8px; bottom: 8px; width: 10px; height: 10px; border-radius: 50%; background: #6c757d; box-shadow: 0 0 0 1px rgba(255,255,255,.6) inset; }
.tp-ping.ok { background: #28a745; }
.tp-ping.err { background: #dc3545; }
</style>
