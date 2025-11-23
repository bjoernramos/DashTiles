<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>toolpages • Start</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Ubuntu,Helvetica,Arial,sans-serif;margin:0;background:#0b1020;color:#e6e9ef}
    .wrap{max-width:1100px;margin:2rem auto;padding:0 1rem}
    .card{background:#121833;border:1px solid #2a3358;border-radius:12px;padding:24px;margin-bottom:1rem}
    h1{margin:0 0 8px 0;font-weight:650}
    code{background:#0b1020;border:1px solid #2a3358;border-radius:6px;padding:2px 6px}
    a{color:#7aa2ff}
    .muted{color:#b7bfcc}
    .row{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .btn{display:inline-block;background:#2a62ff;border:1px solid #2a62ff;color:#fff;padding:.45rem .8rem;border-radius:8px;text-decoration:none}
    .btn.secondary{background:#273158;border-color:#273158}
    .grid{display:grid;gap:12px}
    .tile{background:#0b1020;border:1px solid #2a3358;border-radius:10px;padding:12px}
    .tile h4{margin:0 0 .3rem 0}
    iframe{width:100%;border:none;border-radius:8px;min-height:300px;background:#0b1020}
  </style>
  <base href="<?= htmlspecialchars($basePath ?? '/toolpages', ENT_QUOTES) ?>/">
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="row">
        <h1>toolpages</h1>
        <div>
          <?php if (session()->get('user_id')): ?>
            <span class="muted">Hallo, <?= esc(session()->get('display_name') ?: session()->get('username')) ?></span>
            &nbsp;
            <a class="btn secondary" href="logout">Logout</a>
          <?php else: ?>
            <a class="btn" href="login">Login</a>
          <?php endif; ?>
        </div>
      </div>
      <p class="muted">Base path: <code><?= htmlspecialchars($basePath ?? '/toolpages') ?></code></p>
      <ul>
        <li>Health: <a href="health">/health</a></li>
        <?php if (session()->get('role') === 'admin'): ?>
          <li>Admin Users: <a href="admin/users">/admin/users</a></li>
        <?php endif; ?>
        <?php if (session()->get('user_id')): ?>
          <li>Dashboard verwalten: <a href="dashboard">/dashboard</a></li>
        <?php endif; ?>
      </ul>
      <?php if (!session()->get('user_id')): ?>
        <p>Nach dem Login werden deine Kacheln direkt hier auf der Startseite angezeigt. Unter <code>/dashboard</code> kannst du sie anlegen und bearbeiten.</p>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="card" style="border-color:#7a2a58;background:#3a1430;margin-top:12px"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>
      <?php if (!empty($tiles_error)): ?>
        <div class="card" style="border-color:#7a2a58;background:#3a1430;margin-top:12px"><?= esc($tiles_error) ?></div>
      <?php endif; ?>
    </div>

    <?php if (session()->get('user_id')): ?>
      <?php 
        $cols = isset($columns) ? (int)$columns : 3;
        $cols = max(1, min(6, $cols));
        $gridStyle = 'grid-template-columns: repeat(' . $cols . ', 1fr);';
      ?>
      <?php if (!empty($grouped) && is_array($grouped)): ?>
        <?php foreach ($grouped as $category => $list): ?>
          <div class="card">
            <h3 style="margin:0 0 .5rem 0"><?= esc($category) ?></h3>
            <div class="grid" style="<?= $gridStyle ?>">
              <?php foreach ($list as $tile): ?>
                <div class="tile">
                  <h4 style="display:flex;align-items:center;gap:6px">
                    <?php if (!empty($tile['icon'])): ?>
                      <?php $icon = (string) $tile['icon']; $isImg = str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/'); ?>
                      <?php if ($isImg): ?>
                        <img src="<?= esc($icon) ?>" alt="" style="height:18px;vertical-align:middle;border-radius:3px">
                      <?php else: ?>
                        <span class="<?= esc($icon) ?>" aria-hidden="true"></span>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?= esc($tile['title']) ?>
                  </h4>
                  <?php if ($tile['type'] === 'link'): ?>
                    <p>
                      <a class="btn" href="<?= esc($tile['url']) ?>" target="_blank" rel="noopener">Öffnen</a>
                      <?php if (!empty($tile['text'])): ?>
                        <span class="muted" style="margin-left:.5rem"><?= esc($tile['text']) ?></span>
                      <?php endif; ?>
                    </p>
                  <?php elseif ($tile['type'] === 'iframe'): ?>
                    <iframe src="<?= esc($tile['url']) ?>" loading="lazy"></iframe>
                  <?php elseif ($tile['type'] === 'file'): ?>
                    <p>
                      <a class="btn" href="<?= site_url('file/' . (int)$tile['id']) ?>" target="_blank" rel="noopener">Datei öffnen</a>
                      <?php if (!empty($tile['text'])): ?>
                        <span class="muted" style="margin-left:.5rem"><?= esc($tile['text']) ?></span>
                      <?php endif; ?>
                    </p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="card muted">Noch keine Kacheln vorhanden. Lege welche im <a href="dashboard">Dashboard</a> an.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
