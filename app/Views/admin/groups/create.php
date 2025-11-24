<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc(lang('App.actions.new')) ?> <?= esc(lang('App.pages.groups.index_title')) ?> • <?= esc(lang('App.nav.admin')) ?> • <?= esc(lang('App.brand')) ?></title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body>
  <?= view('partials/nav') ?>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h1 class="h3 m-0"><?= esc(lang('App.actions.new')) ?> <?= esc(lang('App.pages.groups.index_title')) ?></h1>
      <div>
        <a class="btn btn-secondary" href="<?= site_url('admin/groups') ?>"><?= esc(lang('App.actions.back')) ?></a>
      </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="post" action="<?= site_url('admin/groups/store') ?>" class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label" for="name"><?= esc(lang('App.pages.groups.group_name')) ?></label>
            <input id="name" name="name" class="form-control" required>
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit"><?= esc(lang('App.actions.create')) ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
