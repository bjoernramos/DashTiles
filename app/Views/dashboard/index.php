<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mein Dashboard • toolpages</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Inter,Ubuntu,Helvetica,Arial,sans-serif;margin:0;background:#0b1020;color:#e6e9ef}
    .wrap{max-width:1100px;margin:2rem auto;padding:0 1rem}
    .card{background:#121833;border:1px solid #2a3358;border-radius:12px;padding:24px;margin-bottom:1rem}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between}
    .btn{display:inline-block;background:#2a62ff;border:1px solid #2a62ff;color:#fff;padding:.45rem .8rem;border-radius:8px;text-decoration:none;cursor:pointer}
    .btn.secondary{background:#273158;border-color:#273158}
    label{display:block;margin:.35rem 0 .2rem}
    input,select{width:100%;padding:.5rem .6rem;border-radius:8px;border:1px solid #2a3358;background:#0b1020;color:#e6e9ef}
    .grid{display:grid;gap:12px}
    .tile{background:#0b1020;border:1px solid #2a3358;border-radius:10px;padding:12px}
    .tile h4{margin:0 0 .3rem 0}
    .muted{color:#b7bfcc}
    details summary{cursor:pointer}
    iframe{width:100%;border:none;border-radius:8px;min-height:300px;background:#0b1020}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="row">
      <h1 style="margin:0">Mein Dashboard</h1>
      <div>
        <a class="btn secondary" href="/">Zurück</a>
        <a class="btn secondary" href="/logout">Logout</a>
      </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="card" style="border-color:#7a2a58;background:#3a1430"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
      <div class="card" style="border-color:#245d2a;background:#153018"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <div class="card">
      <form class="row" method="post" action="/dashboard/settings">
        <div>
          <label for="columns">Spalten</label>
          <select id="columns" name="columns">
            <?php for ($i=1;$i<=6;$i++): ?>
              <option value="<?= $i ?>" <?= ($columns === $i ? 'selected' : '') ?>><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div style="align-self:end;">
          <button class="btn" type="submit">Layout speichern</button>
        </div>
      </form>
    </div>

    <div class="card">
      <details>
        <summary><strong>Kachel hinzufügen</strong></summary>
        <div class="row" style="margin-top:12px">
          <form method="post" action="/dashboard/tile" style="flex:1 1 320px">
            <input type="hidden" name="type" value="link">
            <h3 style="margin:.2rem 0">Link</h3>
            <label>Titel</label>
            <input name="title" required>
            <label>URL</label>
            <input name="url" placeholder="https://..." required>
            <div class="row">
              <div style="flex:1 1 45%">
                <label>Icon</label>
                <input name="icon" placeholder="z.B. fa-solid fa-link oder Bild-URL">
              </div>
              <div style="flex:1 1 45%">
                <label>Text</label>
                <input name="text" placeholder="optional">
              </div>
            </div>
            <div class="row">
              <div style="flex:1 1 45%">
                <label>Kategorie</label>
                <input name="category" placeholder="z.B. Monitoring">
              </div>
              <div style="flex:1 1 45%">
                <label>Position</label>
                <input name="position" type="number" value="0">
              </div>
            </div>
            <p style="margin-top:8px"><button class="btn" type="submit">Hinzufügen</button></p>
          </form>

          <form method="post" action="/dashboard/tile" enctype="multipart/form-data" style="flex:1 1 320px">
            <input type="hidden" name="type" value="file">
            <h3 style="margin:.2rem 0">Datei</h3>
            <label>Titel</label>
            <input name="title" required>
            <label>Datei</label>
            <input type="file" name="file" required>
            <div class="row">
              <div style="flex:1 1 45%">
                <label>Kategorie</label>
                <input name="category" placeholder="z.B. Doku">
              </div>
              <div style="flex:1 1 45%">
                <label>Position</label>
                <input name="position" type="number" value="0">
              </div>
            </div>
            <p style="margin-top:8px"><button class="btn" type="submit">Hinzufügen</button></p>
          </form>

          <form method="post" action="/dashboard/tile" style="flex:1 1 320px">
            <input type="hidden" name="type" value="iframe">
            <h3 style="margin:.2rem 0">iFrame</h3>
            <label>Titel</label>
            <input name="title" required>
            <label>URL</label>
            <input name="url" placeholder="https://..." required>
            <div class="row">
              <div style="flex:1 1 45%">
                <label>Kategorie</label>
                <input name="category" placeholder="z.B. Dashboards">
              </div>
              <div style="flex:1 1 45%">
                <label>Position</label>
                <input name="position" type="number" value="0">
              </div>
            </div>
            <p style="margin-top:8px"><button class="btn" type="submit">Hinzufügen</button></p>
          </form>
        </div>
      </details>
    </div>

    <?php 
      $cols = max(1, min(6, (int)($columns ?? 3)));
      $gridStyle = 'grid-template-columns: repeat(' . $cols . ', 1fr);';
    ?>
    <?php foreach ($grouped as $category => $list): ?>
      <div class="card">
        <h3 style="margin:0 0 .5rem 0"><?= esc($category) ?></h3>
        <div class="grid" style="<?= $gridStyle ?>">
          <?php foreach ($list as $tile): ?>
            <div class="tile">
              <div class="row" style="justify-content:space-between">
                <h4>
                  <?php if (!empty($tile['icon'])): ?>
                    <?php $icon = (string) $tile['icon']; $isImg = str_starts_with($icon, 'http://') || str_starts_with($icon, 'https://') || str_starts_with($icon, '/'); ?>
                    <?php if ($isImg): ?>
                      <img src="<?= esc($icon) ?>" alt="" style="height:18px;vertical-align:middle;margin-right:6px;border-radius:3px">
                    <?php else: ?>
                      <span class="<?= esc($icon) ?>" aria-hidden="true" style="margin-right:6px"></span>
                    <?php endif; ?>
                  <?php endif; ?>
                  <?= esc($tile['title']) ?>
                </h4>
                <form method="post" action="/dashboard/tile/<?= (int)$tile['id'] ?>/delete" onsubmit="return confirm('Kachel löschen?')">
                  <button class="btn secondary" type="submit">Löschen</button>
                </form>
              </div>
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
                  <a class="btn" href="/file/<?= (int)$tile['id'] ?>" target="_blank" rel="noopener">Datei öffnen</a>
                  <?php if (!empty($tile['text'])): ?>
                    <span class="muted" style="margin-left:.5rem"><?= esc($tile['text']) ?></span>
                  <?php endif; ?>
                </p>
              <?php endif; ?>
              <details>
                <summary class="muted">Bearbeiten</summary>
                <form method="post" action="/dashboard/tile/<?= (int)$tile['id'] ?>" enctype="multipart/form-data" style="margin-top:.5rem">
                  <input type="hidden" name="type" value="<?= esc($tile['type']) ?>">
                  <div class="row">
                    <div style="flex:1 1 45%">
                      <label>Titel</label>
                      <input name="title" value="<?= esc($tile['title']) ?>">
                    </div>
                    <div style="flex:1 1 45%">
                      <label>Kategorie</label>
                      <input name="category" value="<?= esc($tile['category'] ?? '') ?>">
                    </div>
                  </div>
                  <?php if ($tile['type'] !== 'file'): ?>
                    <label>URL</label>
                    <input name="url" value="<?= esc($tile['url'] ?? '') ?>">
                  <?php else: ?>
                    <label>Neue Datei (optional)</label>
                    <input type="file" name="file">
                  <?php endif; ?>
                  <div class="row">
                    <div style="flex:1 1 45%">
                      <label>Icon</label>
                      <input name="icon" value="<?= esc($tile['icon'] ?? '') ?>">
                    </div>
                    <div style="flex:1 1 45%">
                      <label>Text</label>
                      <input name="text" value="<?= esc($tile['text'] ?? '') ?>">
                    </div>
                  </div>
                  <div class="row">
                    <div style="flex:1 1 45%">
                      <label>Position</label>
                      <input name="position" type="number" value="<?= (int)($tile['position'] ?? 0) ?>">
                    </div>
                  </div>
                  <p style="margin-top:8px"><button class="btn" type="submit">Speichern</button></p>
                </form>
              </details>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
