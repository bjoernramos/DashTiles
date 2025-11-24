<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc(lang('App.actions.new')) ?> <?= esc(lang('App.pages.users.index_title')) ?> • <?= esc(lang('App.nav.admin')) ?></title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body>
<?= view('partials/nav') ?>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 m-0"><?= esc(lang('App.actions.new')) ?> <?= esc(lang('App.pages.users.index_title')) ?></h1>
      <a class="btn btn-secondary" href="<?= site_url('admin/users') ?>"><?= esc(lang('App.actions.back')) ?></a>
    </div>
    <div class="card shadow-sm">
      <div class="card-body">
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger" role="alert">Error: <?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= site_url('admin/users/store') ?>">
          <div class="mb-3">
            <label for="username" class="form-label"><?= esc(lang('App.pages.users.username')) ?></label>
            <input id="username" name="username" class="form-control" required>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label for="display_name" class="form-label"><?= esc(lang('App.pages.users.display_name')) ?></label>
              <input id="display_name" name="display_name" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="email" class="form-label"><?= esc(lang('App.pages.users.email')) ?></label>
              <input id="email" name="email" type="email" class="form-control">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label for="password" class="form-label"><?= esc(lang('App.pages.auth.password')) ?></label>
              <input id="password" name="password" type="password" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label for="role" class="form-label"><?= esc(lang('App.pages.users.role')) ?></label>
              <select id="role" name="role" class="form-select">
                <option value="user">user</option>
                <option value="admin">admin</option>
              </select>
            </div>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
            <label class="form-check-label" for="is_active">
              <?= esc(lang('App.pages.users.active')) ?>
            </label>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary" type="submit"><?= esc(lang('App.actions.create')) ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
