<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users • Admin</title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body>
<?= view('partials/nav') ?>
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
              <?php $currentId = (int) (session()->get('user_id') ?? 0); ?>
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
                  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editUserModal<?= (int)$u['id'] ?>">Edit</button>
                  <form method="post" action="<?= site_url('admin/users/' . (int) $u['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Diesen Benutzer inklusive persönlicher Kacheln löschen?');">
                    <button class="btn btn-sm btn-outline-danger" type="submit" <?= ((int)$u['id'] === $currentId ? 'disabled' : '') ?>>Delete</button>
                  </form>
                </td>
              </tr>
              <!-- Edit User Modal -->
              <div class="modal fade" id="editUserModal<?= (int)$u['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit User • <?= esc($u['username']) ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Role</label>
                        <form method="post" action="<?= site_url('admin/users/' . (int)$u['id'] . '/role') ?>" id="formRole<?= (int)$u['id'] ?>">
                          <select name="role" class="form-select">
                            <option value="user" <?= ($u['role'] === 'user' ? 'selected' : '') ?>>user</option>
                            <option value="admin" <?= ($u['role'] === 'admin' ? 'selected' : '') ?>>admin</option>
                          </select>
                        </form>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Active</label>
                        <div>
                          <form method="post" action="<?= site_url('admin/users/' . (int)$u['id'] . '/toggle') ?>" id="formActive<?= (int)$u['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= ((int)$u['is_active'] === 1 ? 'btn-outline-secondary' : 'btn-outline-success') ?>">
                              <?= ((int)$u['is_active'] === 1 ? 'Deactivate' : 'Activate') ?>
                            </button>
                          </form>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-primary" form="formRole<?= (int)$u['id'] ?>">Save</button>
                    </div>
                  </div>
                </div>
              </div>
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
