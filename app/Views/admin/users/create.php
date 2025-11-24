<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create User • Admin</title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body>
<?= view('partials/nav') ?>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 m-0">Create Local User</h1>
      <a class="btn btn-secondary" href="<?= site_url('admin/users') ?>">Back</a>
    </div>
    <div class="card shadow-sm">
      <div class="card-body">
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger" role="alert">Error: <?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= site_url('admin/users/store') ?>">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input id="username" name="username" class="form-control" required>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label for="display_name" class="form-label">Display Name</label>
              <input id="display_name" name="display_name" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="email" class="form-label">Email</label>
              <input id="email" name="email" type="email" class="form-control">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label for="password" class="form-label">Password</label>
              <input id="password" name="password" type="password" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label for="role" class="form-label">Role</label>
              <select id="role" name="role" class="form-select">
                <option value="user">user</option>
                <option value="admin">admin</option>
              </select>
            </div>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
            <label class="form-check-label" for="is_active">
              Active
            </label>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary" type="submit">Create</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
