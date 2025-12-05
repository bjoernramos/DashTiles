<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc(lang('App.pages.dashboard.title')) ?> • <?= esc(lang('App.brand')) ?></title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body class="<?= !empty($backgroundEnabled) ? 'tp-bg-enabled' : '' ?>">
  <?= view('partials/nav') ?>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h1 class="h3 m-0"><?= esc(lang('App.pages.dashboard.title')) ?></h1>
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
              <form class="row g-3 align-items-end" id="saveGrid" method="post" action="/dashboard/settings">

                  <div class="col-auto">
                      <label for="columns" class="form-label mb-0">
                          <?= esc(lang('App.pages.dashboard.columns')) ?>
                      </label>
                      <select id="columns" name="columns" class="form-select" onchange="document.getElementById('saveGrid').requestSubmit()">
                          <?php for ($i = 1; $i <= 6; $i++): ?>
                              <option value="<?= $i ?>" <?= ((int)$columns === $i ? 'selected' : '') ?>>
                                  <?= $i ?>
                              </option>
                          <?php endfor; ?>
                      </select>
                  </div>


                  <div class="col-auto">
                      <button class="btn btn-link p-0 m-0 text-decoration-none text-secondary" type="button"
                              data-bs-toggle="modal" data-bs-target="#addTileModal">
                          <span class="material-symbols-outlined">
                            dashboard_customize
                    </span>
                      </button>
                  </div>

              </form>
          </div>
      </div>



    <?php if (!empty($hiddenTiles)): ?>
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h2 class="h5 m-0"><?= esc(lang('App.pages.dashboard.hidden_title')) ?></h2>
        </div>
        <div class="row g-2">
          <?php foreach ($hiddenTiles as $ht): ?>
            <div class="col-12 col-md-6 col-lg-4">
              <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                <div>
                  <strong><?= esc($ht['title']) ?></strong>
                  <span class="text-muted small ms-2"><?= esc($ht['category'] ?? '') ?></span>
                </div>
                <form method="post" action="/dashboard/tile/<?= (int)$ht['id'] ?>/unhide" class="m-0">
                  <button class="btn btn-sm btn-outline-success" type="submit"><?= esc(lang('App.actions.unhide')) ?></button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="text-muted small mb-3"><?= esc(lang('App.pages.dashboard.drag_hint')) ?></div>
    <?php endif; ?>

    <!-- Add Tile Modal with Tabs -->
    <div class="modal fade" id="addTileModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><?= esc(lang('App.pages.dashboard.add_tile')) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-link" data-bs-toggle="tab" data-bs-target="#pane-link" type="button" role="tab"><?= esc(lang('App.pages.dashboard.tabs.link')) ?></button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-file" data-bs-toggle="tab" data-bs-target="#pane-file" type="button" role="tab"><?= esc(lang('App.pages.dashboard.tabs.file')) ?></button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-iframe" data-bs-toggle="tab" data-bs-target="#pane-iframe" type="button" role="tab"><?= esc(lang('App.pages.dashboard.tabs.iframe')) ?></button>
              </li>
            </ul>
            <div class="tab-content mt-3">
              <div class="tab-pane fade show active" id="pane-link" role="tabpanel" aria-labelledby="tab-link">
                <form method="post" action="/dashboard/tile" id="form-add-link" enctype="multipart/form-data">
                  <input type="hidden" name="type" value="link">
                  <div class="mb-2">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.title')) ?></label>
                    <input name="title" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.url')) ?></label>
                    <input name="url" class="form-control" placeholder="https://..." required>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.icon')) ?></label>
                      <input name="icon" class="form-control" placeholder="z.B. fa-solid fa-link oder Bild-URL">
                    </div>
                    <div class="col-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.text')) ?></label>
                      <input name="text" class="form-control" placeholder="optional">
                    </div>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.category')) ?></label>
                      <input name="category" class="form-control" placeholder="z.B. Monitoring">
                    </div>
                    <!-- position removed: assigned by backend automatically -->
                  </div>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Icon-Bild (optional)</label>
                      <input type="file" name="icon_file" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Hintergrundbild (optional)</label>
                      <input type="file" name="bg_file" class="form-control" accept="image/*">
                    </div>
                  </div>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Hintergrundfarbe (optional)</label>
                      <input type="hidden" name="bg_color_picker_used" value="0">
                      <input type="color" name="bg_color_picker" class="form-control form-control-color" value="#ffffff" oninput="this.form.elements['bg_color_picker_used'].value='1'">
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Farbverlauf/Manuell (CSS)</label>
                      <input type="text" name="bg_color" class="form-control" placeholder="#112233 oder linear-gradient(45deg, #123, #456)" oninput="this.form.elements['bg_color_picker_used'].value=this.value?'0':this.form.elements['bg_color_picker_used'].value">
                      <div class="form-text">Wenn beide gesetzt sind, wird der manuelle Wert verwendet.</div>
                    </div>
                  </div>
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="plink_ping" name="ping_enabled" value="1" checked>
                    <label class="form-check-label" for="plink_ping">Status-Ping anzeigen</label>
                  </div>
                  <?php if (($role) === 'admin'): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_global" value="1" id="lg1">
                    <label class="form-check-label" for="lg1"><?= esc(lang('App.pages.dashboard.labels.global')) ?></label>
                  </div>
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="pfile_ping" name="ping_enabled" value="1" checked>
                    <label class="form-check-label" for="pfile_ping">Status-Ping anzeigen</label>
                  </div>
                  <?php endif; ?>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-lg-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.users')) ?></label>
                      <select class="form-select" name="visible_user_ids[]" multiple size="8">
                        <?php foreach (($usersList ?? []) as $u): ?>
                          <?php $udisp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); ?>
                          <option value="<?= (int)$u['id'] ?>"><?= esc($udisp) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12 col-lg-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.groups')) ?></label>
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
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.title')) ?></label>
                    <input name="title" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.file')) ?></label>
                    <input type="file" name="file" class="form-control" required>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.category')) ?></label>
                      <input name="category" class="form-control" placeholder="z.B. Doku">
                    </div>
                    <!-- position removed: assigned by backend automatically -->
                  </div>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Icon-Bild (optional)</label>
                      <input type="file" name="icon_file" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Hintergrundbild (optional)</label>
                      <input type="file" name="bg_file" class="form-control" accept="image/*">
                    </div>
                  </div>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Hintergrundfarbe (optional)</label>
                      <input type="hidden" name="bg_color_picker_used" value="0">
                      <input type="color" name="bg_color_picker" class="form-control form-control-color" value="#ffffff" oninput="this.form.elements['bg_color_picker_used'].value='1'">
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Farbverlauf/Manuell (CSS)</label>
                      <input type="text" name="bg_color" class="form-control" placeholder="#112233 oder linear-gradient(45deg, #123, #456)" oninput="this.form.elements['bg_color_picker_used'].value=this.value?'0':this.form.elements['bg_color_picker_used'].value">
                      <div class="form-text">Wenn beide gesetzt sind, wird der manuelle Wert verwendet.</div>
                    </div>
                  </div>
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="pfile_ping" name="ping_enabled" value="1" checked>
                    <label class="form-check-label" for="pfile_ping">Status-Ping anzeigen</label>
                  </div>
                  <?php if (($role) === 'admin'): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_global" value="1" id="fg1">
                    <label class="form-check-label" for="fg1"><?= esc(lang('App.pages.dashboard.labels.global')) ?></label>
                  </div>
                  <?php endif; ?>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-lg-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.users')) ?></label>
                      <select class="form-select" name="visible_user_ids[]" multiple size="8">
                        <?php foreach (($usersList ?? []) as $u): ?>
                          <?php $udisp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); ?>
                          <option value="<?= (int)$u['id'] ?>"><?= esc($udisp) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12 col-lg-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.groups')) ?></label>
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
                <form method="post" action="/dashboard/tile" id="form-add-iframe" enctype="multipart/form-data">
                  <input type="hidden" name="type" value="iframe">
                  <div class="mb-2">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.title')) ?></label>
                    <input name="title" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.url')) ?></label>
                    <input name="url" class="form-control" placeholder="https://..." required>
                  </div>
                  <div class="row g-2 mb-2">
                    <div class="col-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.category')) ?></label>
                      <input name="category" class="form-control" placeholder="z.B. Dashboards">
                    </div>
                    <!-- position removed: assigned by backend automatically -->
                  </div>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Icon-Bild (optional)</label>
                      <input type="file" name="icon_file" class="form-control" accept="image/*">
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Hintergrundbild (optional)</label>
                      <input type="file" name="bg_file" class="form-control" accept="image/*">
                    </div>
                  </div>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-md-6">
                      <label class="form-label">Hintergrundfarbe (optional)</label>
                      <input type="hidden" name="bg_color_picker_used" value="0">
                      <input type="color" name="bg_color_picker" class="form-control form-control-color" value="#ffffff" oninput="this.form.elements['bg_color_picker_used'].value='1'">
                    </div>
                    <div class="col-12 col-md-6">
                      <label class="form-label">Farbverlauf/Manuell (CSS)</label>
                      <input type="text" name="bg_color" class="form-control" placeholder="#112233 oder linear-gradient(45deg, #123, #456)" oninput="this.form.elements['bg_color_picker_used'].value=this.value?'0':this.form.elements['bg_color_picker_used'].value">
                      <div class="form-text">Wenn beide gesetzt sind, wird der manuelle Wert verwendet.</div>
                    </div>
                  </div>
                  <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="piframe_ping" name="ping_enabled" value="1" checked>
                    <label class="form-check-label" for="piframe_ping">Status-Ping anzeigen</label>
                  </div>
                  <?php if (($role) === 'admin'): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_global" value="1" id="ig1">
                    <label class="form-check-label" for="ig1"><?= esc(lang('App.pages.dashboard.labels.global')) ?></label>
                  </div>
                  <?php endif; ?>
                  <div class="row g-2 mt-1">
                    <div class="col-12 col-lg-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.users')) ?></label>
                      <select class="form-select" name="visible_user_ids[]" multiple size="8">
                        <?php foreach (($usersList ?? []) as $u): ?>
                          <?php $udisp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); ?>
                          <option value="<?= (int)$u['id'] ?>"><?= esc($udisp) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12 col-lg-6">
                      <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.groups')) ?></label>
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
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= esc(lang('App.actions.close')) ?></button>
            <!-- Default submit is the active tab's form; use JS-free by assigning form attribute to link tab -->
            <button type="submit" class="btn btn-primary" form="form-add-link" id="submitAddTile"><?= esc(lang('App.actions.create')) ?></button>
          </div>
        </div>
      </div>
    </div>

    <?php 
      $cols = max(1, min(6, (int)$columns));
      $colSize = (int) max(1, min(12, floor(12 / $cols)));
    ?>
    <?php foreach ($grouped as $category => $list): ?>
      <div class="card mb-3 category">
        <div class="card-body">
          <h3 class="h5 mb-3"><?= esc($category) ?></h3>
          <div class="row g-3" data-sortable="1" data-category="<?= esc($category) ?>">
          <?php foreach ($list as $tile): ?>
            <div class="col-12 col-md-<?= $colSize ?>">
              <?= view('partials/tile', [
                'tile' => $tile,
                'manageable' => true,
                'userId' => $userId,
                'role' => $role,
                'pingEnabled' => $pingEnabled ?? false
              ]) ?>
            </div>
            
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
              <!-- Edit Modal (unverändert übernommen) -->
              <div class="modal fade" id="editTileModal<?= (int)$tile['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title"><?= esc(lang('App.pages.dashboard.edit_tile')) ?> • <?= esc($tile['title']) ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <form method="post" action="/dashboard/tile/<?= (int)$tile['id'] ?>" enctype="multipart/form-data" id="editForm<?= (int)$tile['id'] ?>">
                <input type="hidden" name="type" value="<?= esc($tile['type']) ?>">
                <div class="row g-2">
                  <div class="col-12">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.title')) ?></label>
                    <input name="title" class="form-control" value="<?= esc($tile['title']) ?>">
                  </div>
                </div>
                <?php if ($tile['type'] !== 'file'): ?>
                  <label class="form-label mt-2"><?= esc(lang('App.pages.dashboard.labels.url')) ?></label>
                  <input name="url" class="form-control" value="<?= esc($tile['url'] ?? '') ?>">
                <?php else: ?>
                  <label class="form-label mt-2"><?= esc(lang('App.pages.dashboard.labels.new_file')) ?></label>
                  <input type="file" name="file" class="form-control">
                <?php endif; ?>
                <div class="row g-2 mt-1">
                  <div class="col-12 col-md-6">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.icon')) ?></label>
                    <input name="icon" class="form-control" value="<?= esc($tile['icon'] ?? '') ?>">
                    <div class="mt-1 d-flex align-items-center gap-2">
                      <?php if (!empty($tile['icon_path'])): ?>
                        <img src="<?= esc(base_url($tile['icon_path'])) ?>" alt="" style="height:24px;border-radius:3px">
                        <button type="submit" name="delete_icon" value="1" class="btn btn-sm btn-outline-danger ms-2">
                          Icon löschen
                        </button>
                      <?php endif; ?>
                      <input type="file" name="icon_file" class="form-control" accept="image/*">
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.text')) ?></label>
                    <input name="text" class="form-control" value="<?= esc($tile['text'] ?? '') ?>">
                  </div>
                </div>
                <div class="mt-2">
                  <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.category')) ?></label>
                  <input name="category" class="form-control" value="<?= esc($tile['category'] ?? '') ?>">
                </div>
                <div class="mt-2">
                  <label class="form-label">Hintergrundbild</label>
                  <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($tile['bg_path'])): ?>
                      <img src="<?= esc(base_url($tile['bg_path'])) ?>" alt="" style="height:32px;border-radius:3px">
                      <button type="submit" name="delete_bg" value="1" class="btn btn-sm btn-outline-danger ms-2">
                        Hintergrund löschen
                      </button>
                    <?php endif; ?>
                    <input type="file" name="bg_file" class="form-control" accept="image/*">
                  </div>
                </div>
                <div class="mt-2">
                  <label class="form-label">Hintergrundfarbe</label>
                  <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                      <input type="hidden" name="bg_color_picker_used" value="0">
                      <input type="hidden" name="bg_color_touch" value="0">
                      <input type="color" name="bg_color_picker" class="form-control form-control-color" value="<?= esc(preg_match('/^#|rgb|hsl|var\(/i', (string)($tile['bg_color'] ?? '')) ? ($tile['bg_color'] ?? '#ffffff') : '#ffffff') ?>" oninput="this.form.elements['bg_color_picker_used'].value='1'; this.form.elements['bg_color_touch'].value='1'">
                    </div>
                    <div class="col-12 col-md-8">
                      <input type="text" name="bg_color" class="form-control" value="<?= esc($tile['bg_color'] ?? '') ?>" placeholder="#112233 oder linear-gradient(45deg, #123, #456)" oninput="this.form.elements['bg_color_touch'].value='1'">
                    </div>
                  </div>
                  <?php if (!empty($tile['bg_color'])): ?>
                    <button type="submit" name="delete_bg_color" value="1" class="btn btn-sm btn-outline-danger mt-2">Farbe/Gradient löschen</button>
                  <?php endif; ?>
                </div>
                <!-- position removed: assignment handled by backend and reorder endpoint -->
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" name="ping_enabled" id="pe<?= (int)$tile['id'] ?>" value="1" <?= ((int)($tile['ping_enabled'] ?? 1) === 1 ? 'checked' : '') ?>>
                  <label class="form-check-label" for="pe<?= (int)$tile['id'] ?>">Status-Ping anzeigen</label>
                </div>
                <?php if (($role ?? 'user') === 'admin'): ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_global" value="1" id="gg<?= (int)$tile['id'] ?>" <?= (!empty($tile['is_global']) && (int)$tile['is_global'] === 1 ? 'checked' : '') ?>>
                    <label class="form-check-label" for="gg<?= (int)$tile['id'] ?>"><?= esc(lang('App.pages.dashboard.labels.global')) ?></label>
                  </div>
                <?php endif; ?>
                <div class="row g-2 mt-1">
                  <div class="col-12 col-md-6">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.allowed_users')) ?></label>
                    <?php $selUsers = (array) (($tileUserMap ?? [])[(int)$tile['id']] ?? []); ?>
                    <select class="form-select" name="visible_user_ids[]" multiple size="8">
                      <?php foreach (($usersList ?? []) as $u): ?>
                        <?php $udisp = trim(($u['display_name'] ?? '') ?: ($u['username'] ?? (string)$u['id'])); ?>
                        <option value="<?= (int)$u['id'] ?>" <?= in_array((int)$u['id'], $selUsers, true) ? 'selected' : '' ?>><?= esc($udisp) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label"><?= esc(lang('App.pages.dashboard.labels.allowed_groups')) ?></label>
                    <?php $selGroups = (array) (($tileGroupMap ?? [])[(int)$tile['id']] ?? []); ?>
                    <select class="form-select" name="visible_group_ids[]" multiple size="8">
                      <?php foreach (($groupsList ?? []) as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= in_array((int)$g['id'], $selGroups, true) ? 'selected' : '' ?>><?= esc($g['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                    </div>
                      </form>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= esc(lang('App.actions.close')) ?></button>
                      <button type="submit" class="btn btn-primary" form="editForm<?= (int)$tile['id'] ?>"><?= esc(lang('App.actions.save')) ?></button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?= view('partials/bootstrap_scripts') ?>
</body>
</html>
