<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc(lang('App.brand')) ?> • <?= esc(lang('App.pages.auth.login')) ?></title>
  <?= view('partials/bootstrap_head') ?>
  <base href="<?= htmlspecialchars($basePath ?? '/toolpages', ENT_QUOTES) ?>/">
</head>
<body>
  <div class="container py-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h3 mb-3"><?= esc(lang('App.pages.auth.login')) ?></h1>
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
          <ul class="nav nav-tabs" id="loginTab" role="tablist">
              <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="localdb-tab" data-bs-toggle="tab" data-bs-target="#localdb" type="button" role="tab" aria-controls="localdb" aria-selected="true"><?= esc(lang('App.pages.auth.tabs.standard')) ?></button>
              </li>
              <li class="nav-item" role="presentation">
                  <button class="nav-link" id="ldap-tab" data-bs-toggle="tab" data-bs-target="#ldap" type="button" role="tab" aria-controls="ldap" aria-selected="false">LDAP</button>
              </li>
          </ul>
          <div class="tab-content" id="loginTabContent" style="margin-top:12px">
              <div class="tab-pane fade show active" id="localdb" role="tabpanel" aria-labelledby="localdb-tab">
                <form method="post" action="login/local" class="mt-3">
                  <h3 class="h5"><?= esc(lang('App.pages.auth.local_login')) ?></h3>
                  <div class="mb-3">
                    <label for="username_local" class="form-label"><?= esc(lang('App.pages.users.username')) ?></label>
                    <input id="username_local" name="username" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label for="password_local" class="form-label"><?= esc(lang('App.pages.auth.password')) ?></label>
                    <input id="password_local" name="password" type="password" class="form-control" required>
                  </div>
                  <button class="btn btn-primary" type="submit"><?= esc(lang('App.pages.auth.login_btn')) ?></button>
                </form>
              </div>
              <div class="tab-pane fade" id="ldap" role="tabpanel" aria-labelledby="ldap-tab">
                <form method="post" action="login/ldap" class="mt-3">
                  <h3 class="h5"><?= esc(lang('App.pages.auth.ldap_login')) ?></h3>
                  <div class="mb-3">
                    <label for="username_ldap" class="form-label"><?= esc(lang('App.pages.users.username')) ?></label>
                    <input id="username_ldap" name="username" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label for="password_ldap" class="form-label"><?= esc(lang('App.pages.auth.password')) ?></label>
                    <input id="password_ldap" name="password" type="password" class="form-control" required>
                  </div>
                  <button class="btn btn-primary" type="submit"><?= esc(lang('App.pages.auth.login_with_ldap')) ?></button>
                </form>
              </div>
          </div>
      </div>
    </div>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
