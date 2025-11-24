<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gruppen • Admin • toolpages</title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body>
  <?= view('partials/nav') ?>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h1 class="h3 m-0">Gruppen</h1>
      <div>
        <a class="btn btn-primary" href="<?= site_url('admin/groups/create') ?>">Neue Gruppe</a>
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
          <table class="table align-middle m-0">
            <thead>
              <tr>
                <th style="width:60px">ID</th>
                <th>Name</th>
                <th style="width:180px">Aktionen</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($groups ?? []) as $g): ?>
                <tr>
                  <td><?= (int)$g['id'] ?></td>
                  <td><?= esc($g['name']) ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/groups/'.(int)$g['id'].'/members') ?>">Mitglieder</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($groups)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Keine Gruppen vorhanden.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
