<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users • Admin</title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 m-0">Admin • Users</h1>
      <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?= site_url('admin/users/create') ?>">Add Local User</a>
        <a class="btn btn-secondary" href="<?= site_url('/') ?>">Back</a>
      </div>
    </div>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success" role="alert"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Username</th>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Source</th>
                <th scope="col">Role</th>
                <th scope="col">Active</th>
                <th scope="col">Actions</th>
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
                  <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/role') ?>" class="m-0">
                    <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                      <option value="user" <?= ($u['role'] === 'user' ? 'selected' : '') ?>>user</option>
                      <option value="admin" <?= ($u['role'] === 'admin' ? 'selected' : '') ?>>admin</option>
                    </select>
                  </form>
                </td>
                <td>
                  <span class="badge <?= ((int)$u['is_active'] === 1 ? 'bg-success' : 'bg-secondary') ?>">
                    <?= ((int) $u['is_active'] === 1 ? 'yes' : 'no') ?>
                  </span>
                </td>
                <td>
                  <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/toggle') ?>" class="d-inline">
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Toggle</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
