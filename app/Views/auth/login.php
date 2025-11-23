<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>toolpages • Login</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Ubuntu,Helvetica,Arial,sans-serif;margin:0;background:#0b1020;color:#e6e9ef}
    .wrap{max-width:900px;margin:4rem auto;padding:0 1rem}
    .card{background:#121833;border:1px solid #2a3358;border-radius:12px;padding:24px}
    h1{margin:0 0 8px 0;font-weight:650}
    label{display:block;margin:.5rem 0 .25rem}
    input{width:100%;padding:.6rem .7rem;border-radius:8px;border:1px solid #2a3358;background:#0b1020;color:#e6e9ef}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .btn{display:inline-block;background:#2a62ff;border:1px solid #2a62ff;color:#fff;padding:.6rem 1rem;border-radius:8px;text-decoration:none;cursor:pointer}
    .muted{color:#b7bfcc}
    .error{background:#3a1430;border:1px solid #7a2a58;padding:.6rem .8rem;border-radius:8px;margin-bottom:1rem}
  </style>
  <base href="<?= htmlspecialchars($basePath ?? '/toolpages', ENT_QUOTES) ?>/">
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Login</h1>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="error"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>
      <div class="row">
        <form method="post" action="login/local">
          <h3>Local Login</h3>
          <label for="username_local">Username</label>
          <input id="username_local" name="username" required>
          <label for="password_local">Password</label>
          <input id="password_local" name="password" type="password" required>
          <p style="margin-top:12px"><button class="btn" type="submit">Login</button></p>
        </form>
        <form method="post" action="login/ldap">
          <h3>LDAP Login</h3>
          <label for="username_ldap">Username</label>
          <input id="username_ldap" name="username" required>
          <label for="password_ldap">Password</label>
          <input id="password_ldap" name="password" type="password" required>
          <p class="muted">Uses configured LDAP directory.</p>
          <p style="margin-top:12px"><button class="btn" type="submit">Login with LDAP</button></p>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
