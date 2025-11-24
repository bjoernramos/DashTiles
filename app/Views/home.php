<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>toolpages • Start</title>
  <?= view('partials/bootstrap_head') ?>
  <base href="<?= htmlspecialchars($basePath ?? '/toolpages', ENT_QUOTES) ?>/">
</head>
<body>
  <?= view('partials/nav') ?>
  <div class="container py-4">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h1 class="h3 m-0">toolpages</h1>
        </div>

        <?php if (!session()->get('user_id')): ?>
          <p class="mb-0">Nach dem Login werden deine Kacheln direkt hier auf der Startseite angezeigt.</p>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger mt-3" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (!empty($tiles_error)): ?>
          <div class="alert alert-warning mt-3" role="alert"><?= esc($tiles_error) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (session()->get('user_id')): ?>
      <?php 
        $cols = isset($columns) ? (int)$columns : 3;
        $cols = max(1, min(6, $cols));
        $colSize = (int) max(1, min(12, floor(12 / $cols)));
      ?>
      <?php if (!empty($grouped) && is_array($grouped)): ?>
        <?php foreach ($grouped as $category => $list): ?>
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <h3 class="h5 mb-3"><?= esc($category) ?></h3>
              <div class="row g-3">
              <?php foreach ($list as $tile): ?>
                <div class="col-12 col-md-<?= $colSize ?>">
                  <?php 
                    $pingUrl = null;
                    if ($tile['type'] === 'file') {
                      $pingUrl = site_url('file/' . (int)$tile['id']);
                    } else {
                      $pingUrl = (string) ($tile['url'] ?? '');
                    }
                  ?>
                  <div class="border rounded p-3 h-100 tp-tile" data-ping-url="<?= esc($pingUrl) ?>">
                  <span class="tp-ping" aria-hidden="true"></span>
                  <h4 class="h6 d-flex align-items-center gap-2 mb-2">
                    <?php if (!empty($tile['icon'])): ?>
                      <?php $icon = (string) $tile['icon']; $isImg = str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/'); ?>
                      <?php if ($isImg): ?>
                        <img src="<?= esc($icon) ?>" alt="" style="height:18px;vertical-align:middle;border-radius:3px">
                      <?php else: ?>
                        <span class="<?= esc($icon) ?>" aria-hidden="true"></span>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?= esc($tile['title']) ?>
                  </h4>
                  <?php if ($tile['type'] === 'link'): ?>
                    <p class="mb-0">
                      <a class="btn btn-primary" href="<?= esc($tile['url']) ?>" target="_blank" rel="noopener">Öffnen</a>
                      <?php if (!empty($tile['text'])): ?>
                        <span class="text-muted ms-2"><?= esc($tile['text']) ?></span>
                      <?php endif; ?>
                    </p>
                  <?php elseif ($tile['type'] === 'iframe'): ?>
                    <iframe src="<?= esc($tile['url']) ?>" loading="lazy" style="width:100%;min-height:300px;border:0;border-radius:.5rem"></iframe>
                  <?php elseif ($tile['type'] === 'file'): ?>
                    <p class="mb-0">
                      <a class="btn btn-primary" href="<?= site_url('file/' . (int)$tile['id']) ?>" target="_blank" rel="noopener">Datei öffnen</a>
                      <?php if (!empty($tile['text'])): ?>
                        <span class="text-muted ms-2"><?= esc($tile['text']) ?></span>
                      <?php endif; ?>
                    </p>
                  <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-info">Noch keine Kacheln vorhanden. Lege welche im <a href="dashboard">Dashboard</a> an.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
