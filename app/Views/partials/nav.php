<?php
$isLoggedIn = session()->get('user_id') ? true : false;
$isAdmin    = session()->get('role') === 'admin';
$locale     = service('request')->getLocale();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= site_url('/') ?>"><?= esc(lang('App.brand')) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#tpNavbar" aria-controls="tpNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="tpNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/') ?>"><?= esc(lang('App.nav.start')) ?></a></li>
        <?php if ($isLoggedIn): ?>
          <li class="nav-item"><a class="nav-link" href="<?= site_url('dashboard') ?>"><?= esc(lang('App.nav.dashboard')) ?></a></li>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= esc(lang('App.nav.admin')) ?></a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= site_url('admin/users') ?>"><?= esc(lang('App.nav.users')) ?></a></li>
              <li><a class="dropdown-item" href="<?= site_url('admin/groups') ?>"><?= esc(lang('App.nav.groups')) ?></a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <?php if ($isLoggedIn): ?>
          <span class="navbar-text d-none d-lg-inline"><?= esc(lang('App.hello')) ?>, <?= esc(session()->get('display_name') ?: session()->get('username')) ?></span>
          <a class="btn btn-outline-light btn-sm" href="<?= site_url('logout') ?>"><?= esc(lang('App.nav.logout')) ?></a>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm" href="<?= site_url('login') ?>"><?= esc(lang('App.nav.login')) ?></a>
        <?php endif; ?>

        <div class="dropdown ms-2">
          <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?= strtoupper(esc($locale)) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item<?= $locale==='de'?' active':'' ?>" href="<?= site_url('lang/de') ?>">Deutsch</a></li>
            <li><a class="dropdown-item<?= $locale==='en'?' active':'' ?>" href="<?= site_url('lang/en') ?>">English</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
 </nav>
