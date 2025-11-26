<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc(lang('App.brand')) ?> • <?= esc(lang('App.pages.home.title')) ?></title>
  <?= view('partials/bootstrap_head') ?>
  <?php
    // BASE_PATH: Wenn Controller/Config keinen Wert liefert, auf Root "/" zurückfallen.
    // Ein festes "/toolpages" als Default hat in der Entwicklung zu 404 bei Plugin-Imports geführt.
    $effectiveBase = isset($basePath) && $basePath !== '' ? $basePath : '/';
  // Saubere Base-URL erzeugen (genau ein abschließender Slash)
  $baseHref = rtrim($effectiveBase, '/');
  if ($baseHref === '') { $baseHref = '/'; }
  $baseHref = rtrim($baseHref, '/') . '/';
  ?>
  <base href="<?= htmlspecialchars($baseHref, ENT_QUOTES) ?>">
</head>
<body <?= session()->get('user_id') ? ('data-user-id="'.(int)session()->get('user_id').'"') : '' ?> >
  <?= view('partials/nav') ?>
  <div class="container py-4">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h1 class="h3 m-0"><?= esc(lang('App.brand')) ?></h1>

        </div>
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <span id="clock"></span>
          </div>

        <?php if (!session()->get('user_id')): ?>
          <p class="mb-0"><?= esc(lang('App.pages.home.welcome_logged_out')) ?></p>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger mt-3" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if (!empty($tiles_error)): ?>
          <div class="alert alert-warning mt-3" role="alert"><?= esc($tiles_error) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development'): ?>
      <!-- Plugin-Demo (nur in Development-Umgebung sichtbar) -->
<!--      <div class="card mb-3">-->
<!--        <div class="card-body">-->
<!--          <h2 class="h6 text-muted mb-2">Plugin-Demo</h2>-->
<!--          <div id="tp-plugin-demo" data-tile-type="demo.hello" class="border rounded p-3" style="min-height:120px"></div>-->
<!--        </div>-->
<!--      </div>-->
    <?php endif; ?>

    <?php if (session()->get('user_id')): ?>
      <?php 
        $cols = isset($columns) ? (int)$columns : 3;
        $cols = max(1, min(6, $cols));
        $colSize = (int) max(1, min(12, floor(12 / $cols)));
      ?>
      <?php if (!empty($grouped) && is_array($grouped)): ?>
        <?php foreach ($grouped as $category => $list): ?>
          <?php $catId = 'cat_'.md5((string)$category); ?>
          <div class="card mb-3 category" data-cat-wrapper="<?= esc($catId) ?>">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-2 category-title">
                <h3 class="h5 m-0"><?= esc($category) ?></h3>
                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#<?= esc($catId) ?>" aria-expanded="true" aria-controls="<?= esc($catId) ?>" title="<?= esc(lang('App.pages.home.collapse_toggle')) ?>">
                  <span class="material-symbols-outlined" aria-hidden="true" data-cat-icon="<?= esc($catId) ?>">expand_less</span>
                  <span class="visually-hidden"><?= esc(lang('App.pages.home.collapse_label')) ?></span>
                </button>
              </div>
              <div id="<?= esc($catId) ?>" class="collapse show" data-cat-id="<?= esc($catId) ?>">
              <div class="row g-3">
              <?php foreach ($list as $tile): ?>
                <div class="col-12 zoom col-md-<?= $colSize ?>">
                  <?php 
                    $pingUrl = null;
                    if ($tile['type'] === 'file') {
                      $pingUrl = site_url('file/' . (int)$tile['id']);
                    } else {
                      $pingUrl = (string) ($tile['url'] ?? '');
                    }
                    $tileHref = null;
                    if ($tile['type'] === 'file') {
                      $tileHref = site_url('file/' . (int)$tile['id']);
                    } elseif ($tile['type'] === 'link') {
                      $tileHref = (string) ($tile['url'] ?? '');
                    } else {
                      $tileHref = null; // avoid making iframe tiles clickable to not interfere with embedded content
                    }
                  ?>
                  <?php 
                    $bgStyle = '';
                    if (!empty($tile['bg_path'])) {
                      $bgStyle = 'background-image:url(' . esc(base_url($tile['bg_path'])) . ');';
                    } elseif (!empty($tile['bg_color'])) {
                      $bgStyle = 'background:' . esc($tile['bg_color']) . ';';
                    }
                    // Per-Tile Ping wirksam berechnen: user setting muss aktiv sein UND Kachel darf Ping nicht explizit deaktivieren
                    $tilePingVal = isset($tile['ping_enabled']) ? $tile['ping_enabled'] : null; // null=inheritiert
                    $userPingOn = (!isset($pingEnabled) || (int)$pingEnabled === 1);
                    $tileAllowsPing = ($tilePingVal === null) ? true : ((int)$tilePingVal === 1);
                    $effectivePing = $userPingOn && $tileAllowsPing;
                  ?>
                  <div class="border rounded p-3 h-100 tp-tile" style="<?= $bgStyle ?>" <?= ($effectivePing ? 'data-ping-url="' . esc($pingUrl) . '"' : '') ?> <?= $tileHref ? ('data-href="' . esc($tileHref) . '"') : '' ?>>
                  <?php if ($effectivePing): ?>
                  <span class="tp-ping" aria-hidden="true"></span>
                  <?php endif; ?>
                  <h4 class="h6 d-flex align-items-center gap-2 mb-2">
                    <?php if (!empty($tile['icon_path'])): ?>
                      <img src="<?= esc(base_url($tile['icon_path'])) ?>" alt="" style="height:18px;vertical-align:middle;border-radius:3px">
                    <?php elseif (!empty($tile['icon'])): ?>
                      <?php $icon = (string) $tile['icon']; $isImg = str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/'); ?>
                      <?php if ($isImg): ?>
                        <img src="<?= esc($icon) ?>" alt="" style="height:18px;vertical-align:middle;border-radius:3px">
                      <?php else: ?>
                        <?php if (str_starts_with($icon, 'line-md:')): ?>
                          <span class="iconify" data-icon="<?= esc($icon) ?>" aria-hidden="true"></span>
                        <?php elseif (str_starts_with($icon, 'mi:')): ?>
                          <?php $iname = substr($icon, 3); ?>
                          <span class="material-icons" aria-hidden="true"><?= esc($iname) ?></span>
                        <?php elseif (str_starts_with($icon, 'ms:')): ?>
                          <?php $iname = substr($icon, 3); ?>
                          <span class="material-symbols-outlined" aria-hidden="true"><?= esc($iname) ?></span>
                        <?php else: ?>
                          <span class="<?= esc($icon) ?>" aria-hidden="true"></span>
                        <?php endif; ?>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?= esc($tile['title']) ?>
                  </h4>
                  <?php if ($tile['type'] === 'link'): ?>
                    <?php if (!empty($tile['text'])): ?>
                      <p class="mb-0 text-muted small"><?= esc($tile['text']) ?></p>
                    <?php endif; ?>
                  <?php elseif ($tile['type'] === 'iframe'): ?>
                    <iframe src="<?= esc($tile['url']) ?>" loading="lazy" style="width:100%;min-height:300px;border:0;border-radius:.5rem"></iframe>
                  <?php elseif ($tile['type'] === 'file'): ?>
                    <?php if (!empty($tile['text'])): ?>
                      <p class="mb-0 text-muted small"><?= esc($tile['text']) ?></p>
                    <?php endif; ?>
                  <?php elseif ($tile['type'] === 'plugin'): ?>
                    <?php
                      $ptype = (string)($tile['plugin_type'] ?? '');
                      $pcfg  = (string)($tile['plugin_config'] ?? '{}');
                      $pcfgOut = $pcfg !== '' ? $pcfg : '{}';
                    ?>
                    <div class="tp-plugin" data-plugin-type="<?= esc($ptype) ?>" data-plugin-cfg='<?= esc($pcfgOut) ?>' style="min-height:80px"></div>
                  <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="alert alert-info"><?= esc(lang('App.pages.home.no_tiles')) ?> <a href="dashboard">Dashboard</a>.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
