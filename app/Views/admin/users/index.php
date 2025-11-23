<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users • Admin</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Ubuntu,Helvetica,Arial,sans-serif;margin:0;background:#0b1020;color:#e6e9ef}
    .wrap{max-width:1000px;margin:2rem auto;padding:0 1rem}
    .card{background:#121833;border:1px solid #2a3358;border-radius:12px;padding:24px}
    table{width:100%;border-collapse:collapse}
    th,td{border-bottom:1px solid #2a3358;padding:.6rem .5rem;text-align:left}
    .btn{display:inline-block;background:#2a62ff;border:1px solid #2a62ff;color:#fff;padding:.35rem .7rem;border-radius:8px;text-decoration:none;cursor:pointer}
    .btn.secondary{background:#273158;border-color:#273158}
    .muted{color:#b7bfcc}
    .row{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="row">
      <h1>Admin • Users</h1>
      <div>
        <a class="btn" href="<?= site_url('admin/users/create') ?>">Add Local User</a>
        <a class="btn secondary" href="<?= site_url('/') ?>">Back</a>
      </div>
    </div>
    <div class="card">
      <?php if (session()->getFlashdata('error')): ?>
        <p class="muted">Error: <?= esc(session()->getFlashdata('error')) ?></p>
      <?php endif; ?>
      <?php if (session()->getFlashdata('success')): ?>
        <p class="muted"><?= esc(session()->getFlashdata('success')) ?></p>
      <?php endif; ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Email</th>
            <th>Source</th>
            <th>Role</th>
            <th>Active</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= (int) $u['id'] ?></td>
            <td><?= esc($u['username']) ?></td>
            <td><?= esc($u['display_name'] ?? '') ?></td>
            <td><?= esc($u['email'] ?? '') ?></td>
            <td><?= esc($u['auth_source']) ?></td>
            <td>
              <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/role') ?>" style="display:inline">
                <select name="role" onchange="this.form.submit()">
                  <option value="user" <?= ($u['role'] === 'user' ? 'selected' : '') ?>>user</option>
                  <option value="admin" <?= ($u['role'] === 'admin' ? 'selected' : '') ?>>admin</option>
                </select>
              </form>
            </td>
            <td><?= ((int) $u['is_active'] === 1 ? 'yes' : 'no') ?></td>
            <td>
              <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/toggle') ?>" style="display:inline">
                <button class="btn secondary" type="submit">Toggle</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
