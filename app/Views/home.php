<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>toolpages • CodeIgniter</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Ubuntu,Helvetica,Arial,sans-serif;margin:0;background:#0b1020;color:#e6e9ef}
    .wrap{max-width:900px;margin:4rem auto;padding:0 1rem}
    .card{background:#121833;border:1px solid #2a3358;border-radius:12px;padding:24px}
    h1{margin:0 0 8px 0;font-weight:650}
    code{background:#0b1020;border:1px solid #2a3358;border-radius:6px;padding:2px 6px}
    a{color:#7aa2ff}
    .muted{color:#b7bfcc}
  .row{display:flex;justify-content:space-between;align-items:center}
  .btn{display:inline-block;background:#2a62ff;border:1px solid #2a62ff;color:#fff;padding:.35rem .7rem;border-radius:8px;text-decoration:none}
  .btn.secondary{background:#273158;border-color:#273158}
  </style>
  <base href="<?= htmlspecialchars($basePath ?? '/toolpages', ENT_QUOTES) ?>/">
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="row">
        <h1>toolpages</h1>
        <div>
          <?php if (session()->get('user_id')): ?>
            <span class="muted">Hello, <?= esc(session()->get('display_name') ?: session()->get('username')) ?></span>
            &nbsp;
            <a class="btn secondary" href="logout">Logout</a>
          <?php else: ?>
            <a class="btn" href="login">Login</a>
          <?php endif; ?>
        </div>
      </div>
      <p class="muted">CodeIgniter 4 is up. Base path: <code><?= htmlspecialchars($basePath ?? '/toolpages') ?></code></p>
      <ul>
        <li>Health: <a href="health">/health</a></li>
        <?php if (session()->get('role') === 'admin'): ?>
          <li>Admin Users: <a href="admin/users">/admin/users</a></li>
        <?php endif; ?>
      </ul>
      <p>Next: authentication (local + LDAP), admin panel, and user management.</p>
    </div>
  </div>
</body>
</html>
