<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mein Dashboard • toolpages</title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body>
  <?= view('partials/nav') ?>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h1 class="h3 m-0">Mein Dashboard</h1>
      <div class="d-flex gap-2">
        <a class="btn btn-secondary" href="/">Zurück</a>
        <a class="btn btn-outline-secondary" href="/logout">Logout</a>
      </div>
    </div>

    <?php 
      // Sichere Defaults, falls Variablen nicht gesetzt sind
      $role = $role ?? 'user';
      $userId = isset($userId) ? (int)$userId : 0;
      $columns = (int)($columns ?? 3);
      $grouped = is_array($grouped ?? null) ? $grouped : [];
    ?>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success" role="alert"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <form class="row g-3 align-items-end" method="post" action="/dashboard/settings">
          <div class="col-auto">
            <label for="columns" class="form-label">Spalten</label>
            <select id="columns" name="columns" class="form-select">
              <?php for ($i=1;$i<=6;$i++): ?>
                <option value="<?= $i ?>" <?= ((int)$columns === $i ? 'selected' : '') ?>><?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="col-auto">
            <button class="btn btn-primary" type="submit">Layout speichern</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <strong>Kachel hinzufügen</strong>
          <div class="text-muted small">Link, Datei oder iFrame per Tabs</div>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTileModal">Neu</button>
      </div>
    </div>

    <!-- Add Tile Modal with Tabs -->
    <div class="modal fade" id="addTileModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Kachel hinzufügen</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-link" data-bs-toggle="tab" data-bs-target="#pane-link" type="button" role="tab">Link</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-file" data-bs-toggle="tab" data-bs-target="#pane-file" type="button" role="tab">Datei</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-iframe" data-bs-toggle="tab" data-bs-target="#pane-iframe" type="button" role="tab">iFrame</button>
              </li>
            </ul>
            <div class="tab-content mt-3">
              <div class="tab-pane fade show active" id="pane-link" role="tabpanel" aria-labelledby="tab-link">
                <form method="post" action="/dashboard/tile" id="form-add-link">
                  <input type="hidden" name="type" value="link">
                  <div class="mb-2">
                    <label class="form-label">Titel</label>
                    <input name="title" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">URL</label>
                    <input name="url" class="form-control" placeholder="https://..." required>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label">Icon</label>
                      <input name="icon" class="form-control" placeholder="z.B. fa-solid fa-link oder Bild-URL">
                    </div>
                    <div class="col-6">
                      <label class="form-label">Text</label>
                      <input name="text" class="form-control" placeholder="optional">
                    </div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label">Kategorie</label>
                      <input name="category" class="form-control" placeholder="z.B. Monitoring">
                    </div>
                    <div class="col-6">
                      <label class="form-label">Position</label>
                      <input name="position" type="number" class="form-control" value="0">
                    </div>
                  </div>
                  <?php if (($role) === 'admin'): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_global" value="1" id="lg1">
                    <label class="form-check-label" for="lg1">Global (für alle Nutzer anzeigen)</label>
                  </div>
                  <?php endif; ?>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-lg-6">
                      <label class="form-label">Nur für bestimmte Benutzer</label>
                      <select class="form-select" name="visible_user_ids[]" multiple size="8">
                        <?php foreach (($usersList ?? []) as $u): ?>
                          <?php $udisp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); ?>
                          <option value="<?= (int)$u['id'] ?>"><?= esc($udisp) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12 col-lg-6">
                      <label class="form-label">Nur für bestimmte Gruppen</label>
                      <select class="form-select" name="visible_group_ids[]" multiple size="8">
                        <?php foreach (($groupsList ?? []) as $g): ?>
                          <option value="<?= (int)$g['id'] ?>"><?= esc($g['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
              <div class="tab-pane fade" id="pane-file" role="tabpanel" aria-labelledby="tab-file">
                <form method="post" action="/dashboard/tile" enctype="multipart/form-data" id="form-add-file">
                  <input type="hidden" name="type" value="file">
                  <div class="mb-2">
                    <label class="form-label">Titel</label>
                    <input name="title" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Datei</label>
                    <input type="file" name="file" class="form-control" required>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label">Kategorie</label>
                      <input name="category" class="form-control" placeholder="z.B. Doku">
                    </div>
                    <div class="col-6">
                      <label class="form-label">Position</label>
                      <input name="position" type="number" class="form-control" value="0">
                    </div>
                  </div>
                  <?php if (($role) === 'admin'): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_global" value="1" id="fg1">
                    <label class="form-check-label" for="fg1">Global (für alle Nutzer anzeigen)</label>
                  </div>
                  <?php endif; ?>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-lg-6">
                      <label class="form-label">Nur für bestimmte Benutzer</label>
                      <select class="form-select" name="visible_user_ids[]" multiple size="8">
                        <?php foreach (($usersList ?? []) as $u): ?>
                          <?php $udisp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); ?>
                          <option value="<?= (int)$u['id'] ?>"><?= esc($udisp) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12 col-lg-6">
                      <label class="form-label">Nur für bestimmte Gruppen</label>
                      <select class="form-select" name="visible_group_ids[]" multiple size="8">
                        <?php foreach (($groupsList ?? []) as $g): ?>
                          <option value="<?= (int)$g['id'] ?>"><?= esc($g['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
              <div class="tab-pane fade" id="pane-iframe" role="tabpanel" aria-labelledby="tab-iframe">
                <form method="post" action="/dashboard/tile" id="form-add-iframe">
                  <input type="hidden" name="type" value="iframe">
                  <div class="mb-2">
                    <label class="form-label">Titel</label>
                    <input name="title" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">URL</label>
                    <input name="url" class="form-control" placeholder="https://..." required>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label">Kategorie</label>
                      <input name="category" class="form-control" placeholder="z.B. Dashboards">
                    </div>
                    <div class="col-6">
                      <label class="form-label">Position</label>
                      <input name="position" type="number" class="form-control" value="0">
                    </div>
                  </div>
                  <?php if (($role) === 'admin'): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_global" value="1" id="ig1">
                    <label class="form-check-label" for="ig1">Global (für alle Nutzer anzeigen)</label>
                  </div>
                  <?php endif; ?>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-lg-6">
                      <label class="form-label">Nur für bestimmte Benutzer</label>
                      <select class="form-select" name="visible_user_ids[]" multiple size="8">
                        <?php foreach (($usersList ?? []) as $u): ?>
                          <?php $udisp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); ?>
                          <option value="<?= (int)$u['id'] ?>"><?= esc($udisp) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12 col-lg-6">
                      <label class="form-label">Nur für bestimmte Gruppen</label>
                      <select class="form-select" name="visible_group_ids[]" multiple size="8">
                        <?php foreach (($groupsList ?? []) as $g): ?>
                          <option value="<?= (int)$g['id'] ?>"><?= esc($g['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
            <!-- Default submit is the active tab's form; use JS-free by assigning form attribute to link tab -->
            <button type="submit" class="btn btn-primary" form="form-add-link" id="submitAddTile">Hinzufügen</button>
          </div>
        </div>
      </div>
    </div>
    <script>
      (function(){
        const btn = document.getElementById('submitAddTile');
        if (!btn) return;
        function setFormByTarget(target){
          switch(target){
            case '#pane-link': btn.setAttribute('form','form-add-link'); break;
            case '#pane-file': btn.setAttribute('form','form-add-file'); break;
            case '#pane-iframe': btn.setAttribute('form','form-add-iframe'); break;
          }
        }
        document.querySelectorAll('#addTileModal [data-bs-toggle="tab"]').forEach(function(tab){
          tab.addEventListener('shown.bs.tab', function(ev){
            const target = ev.target.getAttribute('data-bs-target');
            setFormByTarget(target);
          });
        });
      })();
    </script>

    <?php 
      $cols = max(1, min(6, (int)$columns));
      $colSize = (int) max(1, min(12, floor(12 / $cols)));
    ?>
    <?php foreach ($grouped as $category => $list): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h3 class="h5 mb-3"><?= esc($category) ?></h3>
          <div class="row g-3">
          <?php foreach ($list as $tile): ?>
            <div class="col-12  col-md-<?= $colSize ?>">
              <?php 
                $pingUrl = ($tile['type'] === 'file') ? site_url('file/' . (int)$tile['id']) : (string)($tile['url'] ?? '');
                $tileHref = null;
                if ($tile['type'] === 'file') {
                  $tileHref = site_url('file/' . (int)$tile['id']);
                } elseif ($tile['type'] === 'link') {
                  $tileHref = (string)($tile['url'] ?? '');
                } else {
                  $tileHref = null; // iFrame bleibt eingebettet, keine gesamte Kachel-Klickaktion
                }
              ?>
              <div class="border rounded p-3 h-100 tp-tile" data-ping-url="<?= esc($pingUrl) ?>" <?= $tileHref ? ('data-href="' . esc($tileHref) . '"') : '' ?>>
                <span class="tp-ping" aria-hidden="true"></span>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h4 class="h6 d-flex align-items-center gap-2 m-0">
                    <?php if (!empty($tile['icon'])): ?>
                      <?php $icon = (string) $tile['icon']; $isImg = str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/'); ?>
                      <?php if ($isImg): ?>
                        <img src="<?= esc($icon) ?>" alt="" style="height:18px;vertical-align:middle;border-radius:3px">
                      <?php else: ?>
                        <span class="<?= esc($icon) ?>" aria-hidden="true"></span>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?= esc($tile['title']) ?>
                    <?php if (!empty($tile['is_global']) && (int)$tile['is_global'] === 1): ?>
                      <span class="badge bg-secondary ms-2">Global</span>
                    <?php endif; ?>
                  </h4>
                  <?php 
                    $canManage = false;
                    if (isset($userId)) {
                      $isOwner = ((int)($tile['user_id'] ?? 0) === (int)$userId);
                      $isGlobal = ((int)($tile['is_global'] ?? 0) === 1);
                      $isAdmin = (($role ?? 'user') === 'admin');
                      $canManage = $isOwner || ($isAdmin && $isGlobal);
                    }
                  ?>
                  <?php if ($canManage): ?>
                    <div class="btn-group">
                      <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTileModal<?= (int)$tile['id'] ?>">Bearbeiten</button>
                      <form method="post" action="/dashboard/tile/<?= (int)$tile['id'] ?>/delete" onsubmit="return confirm('Kachel löschen?')" class="m-0 d-inline">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Löschen</button>
                      </form>
                    </div>
                  <?php endif; ?>
                </div>
                <?php if ($tile['type'] === 'link'): ?>
                  <?php if (!empty($tile['text'])): ?>
                    <p class="mb-0 text-muted small"><?= esc($tile['text']) ?></p>
                  <?php endif; ?>
                <?php elseif ($tile['type'] === 'iframe'): ?>
                  <iframe src="<?= esc($tile['url']) ?>" loading="lazy" style="width:100%;min-height:300px;border:0;border-radius:.5rem"></iframe>
                <?php elseif ($tile['type'] === 'file'): ?>
                  <?php if (!empty($tile['text'])): ?>
                    <p class="mb-0 text-muted small"><?= esc($tile['text']) ?></p>
                  <?php endif; ?>
                <?php endif; ?>
                <?php if ($canManage): ?>
                <!-- Edit Tile Modal -->
                <div class="modal fade" id="editTileModal<?= (int)$tile['id'] ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Kachel bearbeiten • <?= esc($tile['title']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <form method="post" action="/dashboard/tile/<?= (int)$tile['id'] ?>" enctype="multipart/form-data" id="editForm<?= (int)$tile['id'] ?>">
                  <input type="hidden" name="type" value="<?= esc($tile['type']) ?>">
                  <div class="row g-2">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Titel</label>
                      <input name="title" class="form-control" value="<?= esc($tile['title']) ?>">
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Kategorie</label>
                      <input name="category" class="form-control" value="<?= esc($tile['category'] ?? '') ?>">
                    </div>
                  </div>
                  <?php if ($tile['type'] !== 'file'): ?>
                    <label class="form-label mt-2">URL</label>
                    <input name="url" class="form-control" value="<?= esc($tile['url'] ?? '') ?>">
                  <?php else: ?>
                    <label class="form-label mt-2">Neue Datei (optional)</label>
                    <input type="file" name="file" class="form-control">
                  <?php endif; ?>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Icon</label>
                      <input name="icon" class="form-control" value="<?= esc($tile['icon'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Text</label>
                      <input name="text" class="form-control" value="<?= esc($tile['text'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Position</label>
                      <input name="position" type="number" class="form-control" value="<?= (int)($tile['position'] ?? 0) ?>">
                    </div>
                  </div>
                  <?php if (($role ?? 'user') === 'admin'): ?>
                    <div class="form-check mt-2">
                      <input class="form-check-input" type="checkbox" name="is_global" value="1" id="gg<?= (int)$tile['id'] ?>" <?= (!empty($tile['is_global']) && (int)$tile['is_global'] === 1 ? 'checked' : '') ?>>
                      <label class="form-check-label" for="gg<?= (int)$tile['id'] ?>">Global (für alle Nutzer anzeigen)</label>
                    </div>
                  <?php endif; ?>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Benutzer, die diese Kachel sehen dürfen</label>
                      <?php $selUsers = (array) (($tileUserMap ?? [])[(int)$tile['id']] ?? []); ?>
                      <select class="form-select" name="visible_user_ids[]" multiple size="8">
                        <?php foreach (($usersList ?? []) as $u): ?>
                          <?php $udisp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); ?>
                          <option value="<?= (int)$u['id'] ?>" <?= in_array((int)$u['id'], $selUsers, true) ? 'selected' : '' ?>><?= esc($udisp) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Gruppen, die diese Kachel sehen dürfen</label>
                      <?php $selGroups = (array) (($tileGroupMap ?? [])[(int)$tile['id']] ?? []); ?>
                      <select class="form-select" name="visible_group_ids[]" multiple size="8">
                        <?php foreach (($groupsList ?? []) as $g): ?>
                          <option value="<?= (int)$g['id'] ?>" <?= in_array((int)$g['id'], $selGroups, true) ? 'selected' : '' ?>><?= esc($g['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                        <button type="submit" class="btn btn-primary" form="editForm<?= (int)$tile['id'] ?>">Speichern</button>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
