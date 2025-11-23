<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create User • Admin</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Ubuntu,Helvetica,Arial,sans-serif;margin:0;background:#0b1020;color:#e6e9ef}
    .wrap{max-width:800px;margin:2rem auto;padding:0 1rem}
    .card{background:#121833;border:1px solid #2a3358;border-radius:12px;padding:24px}
    label{display:block;margin:.5rem 0 .25rem}
    input,select{width:100%;padding:.6rem .7rem;border-radius:8px;border:1px solid #2a3358;background:#0b1020;color:#e6e9ef}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .btn{display:inline-block;background:#2a62ff;border:1px solid #2a62ff;color:#fff;padding:.6rem 1rem;border-radius:8px;text-decoration:none;cursor:pointer}
    .muted{color:#b7bfcc}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Create Local User</h1>
    <p><a class="btn" href="/admin/users">Back</a></p>
    <div class="card">
      <?php if (session()->getFlashdata('error')): ?>
        <p class="muted">Error: <?= esc(session()->getFlashdata('error')) ?></p>
      <?php endif; ?>
      <form method="post" action="/admin/users/store">
        <label for="username">Username</label>
        <input id="username" name="username" required>

        <div class="row">
          <div>
            <label for="display_name">Display Name</label>
            <input id="display_name" name="display_name">
          </div>
          <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email">
          </div>
        </div>

        <div class="row">
          <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
          </div>
          <div>
            <label for="role">Role</label>
            <select id="role" name="role">
              <option value="user">user</option>
              <option value="admin">admin</option>
            </select>
          </div>
        </div>

        <label><input type="checkbox" name="is_active" value="1" checked> Active</label>

        <p style="margin-top:12px"><button class="btn" type="submit">Create</button></p>
      </form>
    </div>
  </div>
</body>
</html>
