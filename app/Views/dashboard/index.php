<!doctype html>
<html lang="de">
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
      <div class="card-body">
        <details>
          <summary><strong>Kachel hinzufügen</strong></summary>
          <div class="row g-3 mt-3">
            <form method="post" action="/dashboard/tile" class="col-12 col-lg-4">
            <input type="hidden" name="type" value="link">
            <h3 class="h6 mt-1">Link</h3>
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
            <div class="mt-2"><button class="btn btn-primary" type="submit">Hinzufügen</button></div>
          </form>

          <form method="post" action="/dashboard/tile" enctype="multipart/form-data" class="col-12 col-lg-4">
            <input type="hidden" name="type" value="file">
            <h3 class="h6 mt-1">Datei</h3>
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
            <div class="mt-2"><button class="btn btn-primary" type="submit">Hinzufügen</button></div>
          </form>

          <form method="post" action="/dashboard/tile" class="col-12 col-lg-4">
            <input type="hidden" name="type" value="iframe">
            <h3 class="h6 mt-1">iFrame</h3>
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
            <div class="mt-2"><button class="btn btn-primary" type="submit">Hinzufügen</button></div>
          </form>
          </div>
        </details>
      </div>
    </div>

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
              <div class="border rounded p-3 h-100">
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
                    <form method="post" action="/dashboard/tile/<?= (int)$tile['id'] ?>/delete" onsubmit="return confirm('Kachel löschen?')" class="m-0">
                      <button class="btn btn-sm btn-outline-secondary" type="submit">Löschen</button>
                    </form>
                  <?php endif; ?>
                </div>
                <?php if ($tile['type'] === 'link'): ?>
                  <p class="mb-0">
                    <a class="btn btn-primary" href="<?= esc($tile['url']) ?>" target="_blank" rel="noopener">Öffnen</a>
                    <?php if (!empty($tile['text'])): ?>
                      <span class="text-muted ms-2"><?= esc($tile['text']) ?></span>
                    <?php endif; ?>
                  </p>
                <?php elseif ($tile['type'] === 'iframe'): ?>
                  <iframe src="<?= esc($tile['url']) ?>" loading="lazy" style="width:100%;min-height:300px;border:0;border-radius:.5rem"></iframe>
                <?php elseif ($tile['type'] === 'file'): ?>
                  <p class="mb-0">
                    <a class="btn btn-primary" href="/file/<?= (int)$tile['id'] ?>" target="_blank" rel="noopener">Datei öffnen</a>
                    <?php if (!empty($tile['text'])): ?>
                      <span class="text-muted ms-2"><?= esc($tile['text']) ?></span>
                    <?php endif; ?>
                  </p>
                <?php endif; ?>
                <?php if ($canManage): ?>
                <details>
                  <summary class="text-muted">Bearbeiten</summary>
                  <form method="post" action="/dashboard/tile/<?= (int)$tile['id'] ?>" enctype="multipart/form-data" class="mt-2">
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
                  <div class="mt-2"><button class="btn btn-primary" type="submit">Speichern</button></div>
                </form>
                </details>
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
