<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profil • <?= esc(lang('App.brand')) ?></title>
  <?= view('partials/bootstrap_head') ?>
</head>
<body>
<?= view('partials/nav') ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h1 class="h3 m-0">Mein Profil</h1>
    <a class="btn btn-secondary" href="<?= site_url('dashboard') ?>">Zurück zum Dashboard</a>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success" role="alert"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <?php 
    $user = $user ?? [];
    $settings = $settings ?? [];
    $authSource = $user['auth_source'] ?? 'local';
    $avatar = $user['profile_image'] ?? null;
  ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <ul class="nav nav-tabs" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-profile" data-bs-toggle="tab" data-bs-target="#pane-profile" type="button" role="tab">Profil</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-password" data-bs-toggle="tab" data-bs-target="#pane-password" type="button" role="tab">Passwort</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-avatar" data-bs-toggle="tab" data-bs-target="#pane-avatar" type="button" role="tab">Profilbild</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-settings" data-bs-toggle="tab" data-bs-target="#pane-settings" type="button" role="tab">Einstellungen</button>
        </li>
      </ul>

      <div class="tab-content pt-3">
        <!-- Profil -->
        <div class="tab-pane fade show active" id="pane-profile" role="tabpanel">
          <form method="post" action="<?= site_url('profile/profile') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-6">
              <label class="form-label">Anzeigename</label>
              <input type="text" name="display_name" class="form-control" value="<?= esc($user['display_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-Mail</label>
              <input type="email" name="email" class="form-control" value="<?= esc($user['email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Vorname</label>
              <input type="text" name="first_name" class="form-control" value="<?= esc($user['first_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nachname</label>
              <input type="text" name="last_name" class="form-control" value="<?= esc($user['last_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefonnummer</label>
              <input type="text" name="phone" class="form-control" value="<?= esc($user['phone'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Adresse</label>
              <input type="text" name="address" class="form-control" value="<?= esc($user['address'] ?? '') ?>">
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit">Speichern</button>
            </div>
          </form>
        </div>

        <!-- Passwort -->
        <div class="tab-pane fade" id="pane-password" role="tabpanel">
          <?php if ($authSource !== 'local'): ?>
            <div class="alert alert-info mb-3">
              Dieser Account wird über LDAP verwaltet. Passwortänderungen sind hier deaktiviert.
            </div>
          <?php endif; ?>
          <form method="post" action="<?= site_url('profile/password') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-6">
              <label class="form-label">Aktuelles Passwort</label>
              <input type="password" name="current_password" class="form-control" <?= $authSource !== 'local' ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-6"></div>
            <div class="col-md-6">
              <label class="form-label">Neues Passwort</label>
              <input type="password" name="new_password" class="form-control" <?= $authSource !== 'local' ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-6">
              <label class="form-label">Neues Passwort (Bestätigung)</label>
              <input type="password" name="confirm_password" class="form-control" <?= $authSource !== 'local' ? 'disabled' : '' ?>>
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit" <?= $authSource !== 'local' ? 'disabled' : '' ?>>Passwort speichern</button>
            </div>
          </form>
        </div>

        <!-- Avatar -->
        <div class="tab-pane fade" id="pane-avatar" role="tabpanel">
          <div class="row g-3 align-items-center">
            <div class="col-md-3">
              <?php if ($avatar): ?>
                <img src="<?= esc(base_url($avatar)) ?>" class="img-fluid rounded border" alt="Avatar">
              <?php else: ?>
                <div class="text-muted small">Noch kein Profilbild.</div>
              <?php endif; ?>
            </div>
            <div class="col-md-9">
              <form method="post" action="<?= site_url('profile/avatar') ?>" enctype="multipart/form-data" class="d-flex gap-2 align-items-end">
                <?= csrf_field() ?>
                <div>
                  <label class="form-label">Bild auswählen</label>
                  <input type="file" name="avatar" class="form-control" accept="image/*">
                  <div class="form-text">PNG, JPG, WEBP oder GIF. Max. ~5 MB (Serverlimit).</div>
                </div>
                <div>
                  <button class="btn btn-primary" type="submit">Hochladen</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Einstellungen -->
        <div class="tab-pane fade" id="pane-settings" role="tabpanel">
          <form method="post" action="<?= site_url('profile/settings') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-12 form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="ping_enabled" name="ping_enabled" value="1" <?= ((int)($settings['ping_enabled'] ?? 1) === 1) ? 'checked' : '' ?>>
              <label class="form-check-label" for="ping_enabled">Status-Pings anzeigen</label>
            </div>
            <div class="col-12 form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="background_enabled" name="background_enabled" value="1" <?= ((int)($settings['background_enabled'] ?? 0) === 1) ? 'checked' : '' ?>>
              <label class="form-check-label" for="background_enabled">Hintergrundbild aktivieren</label>
            </div>
            <hr class="mt-2">
            <div class="col-12">
              <h6 class="mb-2">Sitzungsdauer bei Inaktivität</h6>
            </div>
            <?php 
              $sessSecs = (int)($settings['session_duration'] ?? 7200);
              if ($sessSecs < 0) { $sessSecs = 0; }
              $prefUnit = 'minutes';
              $prefValue = 0;
              if ($sessSecs === 0) {
                  $prefUnit = 'minutes';
                  $prefValue = 0;
              } elseif ($sessSecs % 604800 === 0) { // weeks
                  $prefUnit = 'weeks';
                  $prefValue = (int)($sessSecs / 604800);
              } elseif ($sessSecs % 86400 === 0) { // days
                  $prefUnit = 'days';
                  $prefValue = (int)($sessSecs / 86400);
              } else { // minutes
                  $prefUnit = 'minutes';
                  $prefValue = (int)max(1, round($sessSecs / 60));
              }
            ?>
            <div class="col-md-4">
              <label class="form-label" for="session_value">Wert</label>
              <input type="number" min="0" step="1" id="session_value" name="session_value" class="form-control" value="<?= esc($prefValue) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="session_unit">Einheit</label>
              <select class="form-select" id="session_unit" name="session_unit">
                <option value="minutes" <?= $prefUnit==='minutes'?'selected':'' ?>>Minuten</option>
                <option value="days" <?= $prefUnit==='days'?'selected':'' ?>>Tage</option>
                <option value="weeks" <?= $prefUnit==='weeks'?'selected':'' ?>>Wochen</option>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-text">Keine Mindest-/Höchstgrenze. 0 = bis zum Schließen des Browsers. Wird in Sekunden gespeichert.</div>
            </div>
            <hr class="mt-2">
            <div class="col-12">
              <h6 class="mb-2">Suche in der Kopfzeile</h6>
            </div>
            <div class="col-12 form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="search_tile_enabled" name="search_tile_enabled" value="1" <?= ((int)($settings['search_tile_enabled'] ?? 1) === 1) ? 'checked' : '' ?>>
              <label class="form-check-label" for="search_tile_enabled">Suchleiste anzeigen</label>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="search_engine">Suchmaschine</label>
              <?php $engine = $settings['search_engine'] ?? 'google'; ?>
              <select class="form-select" id="search_engine" name="search_engine">
                <option value="google" <?= $engine==='google'?'selected':'' ?>>Google</option>
                <option value="duckduckgo" <?= $engine==='duckduckgo'?'selected':'' ?>>DuckDuckGo</option>
                <option value="bing" <?= $engine==='bing'?'selected':'' ?>>Bing</option>
                <option value="startpage" <?= $engine==='startpage'?'selected':'' ?>>Startpage</option>
                <option value="ecosia" <?= $engine==='ecosia'?'selected':'' ?>>Ecosia</option>
              </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="search_autofocus" name="search_autofocus" value="1" <?= ((int)($settings['search_autofocus'] ?? 0) === 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="search_autofocus">Eingabefeld automatisch fokussieren</label>
              </div>
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit">Einstellungen speichern</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= esc(base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
</body>
</html>
