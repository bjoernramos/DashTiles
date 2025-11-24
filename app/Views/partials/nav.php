<?php
// Responsive Navigation Bar (inline CSS/JS)
$isLoggedIn = session()->get('user_id') ? true : false;
$isAdmin    = session()->get('role') === 'admin';
?>
<style>
  .tp-nav{background:#0b1020;border-bottom:1px solid #2a3358;position:sticky;top:0;z-index:100}
  .tp-nav .wrap{max-width:1100px;margin:0 auto;padding:.6rem 1rem;display:flex;align-items:center;justify-content:space-between}
  .tp-brand{display:flex;align-items:center;gap:.5rem;color:#e6e9ef;font-weight:650;text-decoration:none}
  .tp-menu{display:flex;align-items:center;gap:.6rem}
  .tp-menu a{color:#e6e9ef;text-decoration:none;padding:.35rem .6rem;border-radius:8px}
  .tp-menu a:hover{background:#121833}
  .tp-right{display:flex;align-items:center;gap:.6rem}
  .tp-btn{display:inline-block;background:#2a62ff;border:1px solid #2a62ff;color:#fff;padding:.35rem .7rem;border-radius:8px;text-decoration:none}
  .tp-toggle{display:none;background:#121833;color:#e6e9ef;border:1px solid #2a3358;border-radius:8px;padding:.35rem .6rem}
  @media (max-width: 720px){
    .tp-menu{display:none;flex-direction:column;align-items:flex-start;padding:.5rem 1rem}
    .tp-menu.open{display:flex}
    .tp-right{display:none}
    .tp-toggle{display:inline-block}
  }
</style>
<nav class="tp-nav">
  <div class="wrap">
    <a class="tp-brand" href="<?= site_url('/') ?>" aria-label="toolpages Home">toolpages</a>
    <div class="tp-right">
      <?php if ($isLoggedIn): ?>
        <span style="color:#b7bfcc;white-space:nowrap">Hallo, <?= esc(session()->get('display_name') ?: session()->get('username')) ?></span>
      <?php endif; ?>
      <button class="tp-toggle" type="button" aria-expanded="false" aria-controls="tp-menu">Menü</button>
    </div>
  </div>
  <div id="tp-menu" class="wrap tp-menu" role="navigation">
    <a href="<?= site_url('/') ?>">Start</a>
    <?php if ($isLoggedIn): ?>
      <a href="<?= site_url('dashboard') ?>">Dashboard</a>
    <?php endif; ?>
    <?php if ($isAdmin): ?>
      <a href="<?= site_url('admin/users') ?>">Admin • Users</a>
    <?php endif; ?>
    <span style="flex:1"></span>
    <?php if ($isLoggedIn): ?>
      <a class="tp-btn" href="<?= site_url('logout') ?>">Logout</a>
    <?php else: ?>
      <a class="tp-btn" href="<?= site_url('login') ?>">Login</a>
    <?php endif; ?>
  </div>
</nav>
<script>
  (function(){
    const toggle = document.currentScript.previousElementSibling.previousElementSibling.querySelector('.tp-toggle');
    const menu = document.getElementById('tp-menu');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', function(){
      const opened = menu.classList.toggle('open');
      toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
    });
  })();
</script>
