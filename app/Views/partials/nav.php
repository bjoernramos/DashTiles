<?php
$isLoggedIn = session()->get('user_id') ? true : false;
$isAdmin    = session()->get('role') === 'admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= site_url('/') ?>">toolpages</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#tpNavbar" aria-controls="tpNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="tpNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= site_url('/') ?>">Start</a></li>
        <?php if ($isLoggedIn): ?>
          <li class="nav-item"><a class="nav-link" href="<?= site_url('dashboard') ?>">Dashboard</a></li>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= site_url('admin/users') ?>">Users</a></li>
              <li><a class="dropdown-item" href="<?= site_url('admin/groups') ?>">Gruppen</a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <?php if ($isLoggedIn): ?>
          <span class="navbar-text d-none d-lg-inline">Hallo, <?= esc(session()->get('display_name') ?: session()->get('username')) ?></span>
          <a class="btn btn-outline-light btn-sm" href="<?= site_url('logout') ?>">Logout</a>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm" href="<?= site_url('login') ?>">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
 </nav>
