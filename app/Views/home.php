<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc(lang('App.brand')) ?> • <?= esc(lang('App.pages.home.title')) ?></title>
  <?= view('partials/bootstrap_head') ?>
  <base href="<?= htmlspecialchars($basePath ?? '/toolpages', ENT_QUOTES) ?>/">
</head>
<body <?= session()->get('user_id') ? ('data-user-id="'.(int)session()->get('user_id').'"') : '' ?> >
  <?= view('partials/nav') ?>
  <div class="container py-4 header-tile">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h1 class="h3 m-0"><?= esc(lang('App.brand')) ?></h1>

        </div>
        <?php $s = $settings ?? null; ?>
        <?php if (session()->get('user_id') && is_array($s) && (int)($s['search_tile_enabled'] ?? 1) === 1): ?>
          <?php $engine = (string)($s['search_engine'] ?? 'google'); $af = (int)($s['search_autofocus'] ?? 0); ?>
          <form class="mt-2" id="tp-header-search" data-tp-search="1" data-engine="<?= esc($engine) ?>" data-autofocus="<?= $af ? '1' : '0' ?>" role="search" onsubmit="return false;">
            <div class="input-group">
              <span class="input-group-text"><span class="material-symbols-outlined" aria-hidden="true">search</span></span>
              <input type="search" class="form-control" id="tp-header-search-input" name="q" placeholder="Suche im Web" autocomplete="off">
              <button class="btn btn-primary" type="submit">Suchen</button>
            </div>
            <div class="form-text">Eingabe + Enter öffnet die Suche in einem neuen Tab.</div>
          </form>
        <?php endif; ?>
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
              <div class="d-flex align-items-center justify-content-between mb-2 category-head">
                <h3 class="h5 m-0"><?= esc($category) ?></h3>
                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#<?= esc($catId) ?>" aria-expanded="true" aria-controls="<?= esc($catId) ?>" title="<?= esc(lang('App.pages.home.collapse_toggle')) ?>">
                  <span class="material-symbols-outlined" aria-hidden="true" data-cat-icon="<?= esc($catId) ?>">expand_less</span>
                  <span class="visually-hidden"><?= esc(lang('App.pages.home.collapse_label')) ?></span>
                </button>
              </div>
              <div id="<?= esc($catId) ?>" class="collapse show" data-cat-id="<?= esc($catId) ?>">
              <div class="row g-3 category-body">
              <?php foreach ($list as $tile): ?>
                <div class="col-12 zoom col-md-<?= $colSize ?>">
                  <?= view('partials/tile', [
                    'tile' => $tile,
                    'manageable' => false,
                    'settings' => $settings ?? []
                  ]) ?>
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
