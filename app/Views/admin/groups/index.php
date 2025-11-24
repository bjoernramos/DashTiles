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
                <th>Mitglieder</th>
                <th style="width:320px">Aktionen</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (($groups ?? []) as $g): ?>
                <tr>
                  <td><?= (int)$g['id'] ?></td>
                  <td><?= esc($g['name']) ?></td>
                  <td>
                    <?php 
                      $uids = ($groupUserIds[$g['id']] ?? []);
                      $allUsers = $users ?? [];
                      $labels = [];
                      $indexById = [];
                      foreach ($allUsers as $u) { $indexById[(int)$u['id']] = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); }
                      foreach ($uids as $uid) { $labels[] = esc($indexById[(int)$uid] ?? ('#'.(int)$uid)); }
                      $preview = implode(', ', array_slice($labels, 0, 5));
                    ?>
                    <span class="text-muted small"><?= $preview !== '' ? $preview : '–' ?><?= count($labels) > 5 ? ' …' : '' ?></span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editMembersModal<?= (int)$g['id'] ?>">Mitglieder</button>
                    <form method="post" action="<?= site_url('admin/groups/'.(int)$g['id'].'/delete') ?>" class="d-inline" onsubmit="return confirm('Gruppe wirklich löschen? Zugehörige Mitgliedschaften und Kachel-Zuordnungen werden entfernt.');">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Löschen</button>
                    </form>
                  </td>
                </tr>
                <!-- Edit Members Modal -->
                <div class="modal fade" id="editMembersModal<?= (int)$g['id'] ?>" tabindex="-1" aria-labelledby="editMembersLabel<?= (int)$g['id'] ?>" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="editMembersLabel<?= (int)$g['id'] ?>">Mitglieder verwalten • <?= esc($g['name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <form method="post" action="<?= site_url('admin/groups/'.(int)$g['id'].'/members') ?>" id="membersForm<?= (int)$g['id'] ?>">
                          <label class="form-label">Benutzer in dieser Gruppe</label>
                          <select class="form-select" name="user_ids[]" multiple size="12">
                            <?php foreach (($users ?? []) as $u): ?>
                              <?php $disp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? ('#'.$u['id']))); ?>
                              <option value="<?= (int)$u['id'] ?>" <?= in_array((int)$u['id'], ($groupUserIds[$g['id']] ?? []), true) ? 'selected' : '' ?>>
                                <?= esc($disp) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                        <button type="submit" class="btn btn-primary" form="membersForm<?= (int)$g['id'] ?>">Speichern</button>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              <?php if (empty($groups)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Keine Gruppen vorhanden.</td></tr>
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
