<?php
/**
 * Tile Partial View
 * 
 * @var array  $tile         Die Tile-Daten
 * @var bool   $manageable   Verwaltungsmodus? Default: false
 * @var int    $userId       User-ID für Berechtigungen (optional)
 * @var string $role         User-Rolle (optional)
 * @var bool   $pingEnabled  Ping aktiviert? Dashboard (optional)
 * @var array  $settings     User-Settings für Home (optional)
 */

// Defaults
$manageable = $manageable ?? false;
$userId = $userId ?? null;
$role = $role ?? 'user';
$pingEnabled = $pingEnabled ?? false;
$settings = $settings ?? [];

// Tile-Daten
$tileId = (int) $tile['id'];
$tileType = $tile['type'] ?? 'link';
$tileTitle = $tile['title'] ?? '';
$tileUrl = $tile['url'] ?? '';
$tileText = $tile['text'] ?? '';
$tileIcon = $tile['icon'] ?? '';
$tileIconPath = $tile['icon_path'] ?? '';
$tileBgPath = $tile['bg_path'] ?? '';
$tileBgColor = $tile['bg_color'] ?? '';
$tilePingEnabled = $tile['ping_enabled'] ?? 1;
$tileIsGlobal = (int) ($tile['is_global'] ?? 0) === 1;
$tileUserId = (int) ($tile['user_id'] ?? 0);

// URLs
$pingUrl = ($tileType === 'file') ? site_url('file/' . $tileId) : $tileUrl;
$tileHref = null;
if ($tileType === 'file') {
  $tileHref = site_url('file/' . $tileId);
} elseif ($tileType === 'link') {
  $tileHref = $tileUrl;
}

// Background
$bgStyle = '';
if (!empty($tileBgPath)) {
  $bgStyle = 'background-image:url(' . esc(base_url($tileBgPath)) . ');';
} elseif (!empty($tileBgColor)) {
  $bgStyle = 'background:' . esc($tileBgColor) . ';';
}

// Berechtigungen
$canManage = false;
if ($manageable && isset($userId)) {
  $isOwner = ($tileUserId === (int)$userId);
  $isAdmin = ($role === 'admin');
  $canManage = $isOwner || ($isAdmin && $tileIsGlobal);
}

// CSS & Attribute
$tileClass = $manageable ? 'tp-tile' : 'tp-tiles';
$tileAttrs = $manageable ? 'data-tile-id="' . $tileId . '" draggable="true"' : '';

// Ping anzeigen?
$showPing = false;
if ($manageable) {
  $showPing = !empty($pingEnabled) && ((int)$tilePingEnabled === 1);
} else {
  $showPing = ((int)($settings['ping_enabled'] ?? 1) === 1) && ((int)$tilePingEnabled === 1);
}
?>

<div class="border rounded p-3 h-100 <?= $tileClass ?>" 
     style="<?= $bgStyle ?>" 
     data-ping-url="<?= esc($pingUrl) ?>" 
     <?= $tileHref ? 'data-href="' . esc($tileHref) . '"' : '' ?>
     <?= $tileAttrs ?>>
  
  <?php if ($showPing): ?>
    <span class="tp-ping" aria-hidden="true"></span>
  <?php endif; ?>
  
  <div class="<?= $manageable ? 'd-flex justify-content-between align-items-center mb-2' : '' ?>">
    <h4 class="h6 d-flex align-items-center gap-2 <?= $manageable ? 'm-0' : 'mb-2' ?>">
      <!-- Icon -->
      <?php if (!empty($tileIconPath)): ?>
        <img src="<?= esc(base_url($tileIconPath)) ?>" alt="" loading="lazy" 
             style="height:18px;vertical-align:middle;border-radius:3px">
      <?php elseif (!empty($tileIcon)): ?>
        <?php 
          $isImg = str_starts_with($tileIcon, 'http://') 
                || str_starts_with($tileIcon, 'https://') 
                || str_starts_with($tileIcon, '/'); 
        ?>
        <?php if ($isImg): ?>
          <img src="<?= esc($tileIcon) ?>" alt="" loading="lazy"
               style="height:18px;vertical-align:middle;border-radius:3px">
        <?php else: ?>
          <?php if (str_starts_with($tileIcon, 'line-md:')): ?>
            <span class="iconify" data-icon="<?= esc($tileIcon) ?>" aria-hidden="true"></span>
          <?php elseif (str_starts_with($tileIcon, 'mi:')): ?>
            <span class="material-icons" aria-hidden="true"><?= esc(substr($tileIcon, 3)) ?></span>
          <?php elseif (str_starts_with($tileIcon, 'ms:')): ?>
            <span class="material-symbols-outlined" aria-hidden="true"><?= esc(substr($tileIcon, 3)) ?></span>
          <?php else: ?>
            <span class="<?= esc($tileIcon) ?>" aria-hidden="true"></span>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
      
      <?= esc($tileTitle) ?>
      
      <?php if ($manageable && $tileIsGlobal): ?>
        <span class="badge bg-secondary ms-2"><?= esc(lang('App.pages.dashboard.global_badge')) ?></span>
      <?php endif; ?>
    </h4>
    
    <?php if ($manageable && ($canManage || $tileIsGlobal)): ?>
    <!-- Three-Dot-Menü -->
    <div class="dropdown">
      <button class="btn btn-sm text-secondary border-0 bg-transparent p-1" 
              type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="material-symbols-outlined">more_vert</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <?php if ($canManage): ?>
        <li>
          <button class="dropdown-item" type="button" 
                  data-bs-toggle="modal" data-bs-target="#editTileModal<?= $tileId ?>">
            <span class="material-symbols-outlined me-2">edit</span>
            <?= esc(lang('App.actions.edit') ?? 'Bearbeiten') ?>
          </button>
        </li>
        <li><hr class="dropdown-divider"></li>
        <?php endif; ?>
        
        <?php if ($tileIsGlobal): ?>
        <li>
          <form method="post" action="/dashboard/tile/<?= $tileId ?>/hide" class="m-0">
            <button class="dropdown-item" type="submit">
              <span class="material-symbols-outlined me-2">visibility_off</span>
              <?= esc(lang('App.actions.hide') ?? 'Ausblenden') ?>
            </button>
          </form>
        </li>
        <?php endif; ?>
        
        <?php if ($canManage): ?>
        <li>
          <button class="dropdown-item text-danger" type="button" 
                  data-action="delete-tile" 
                  data-tile-id="<?= $tileId ?>" 
                  data-delete-url="<?= esc(site_url('dashboard/tile/' . $tileId . '/delete')) ?>" 
                  data-confirm-text="<?= esc(lang('App.pages.dashboard.delete_tile_confirm')) ?>">
            <span class="material-symbols-outlined me-2">delete</span>
            <?= esc(lang('App.actions.delete') ?? 'Löschen') ?>
          </button>
        </li>
        <?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
  
  <!-- Content -->
  <?php if ($tileType === 'link'): ?>
    <?php if (!empty($tileText)): ?>
      <p class="mb-0 text-muted small"><?= esc($tileText) ?></p>
    <?php endif; ?>
  <?php elseif ($tileType === 'iframe'): ?>
    <iframe src="<?= esc($tileUrl) ?>" loading="lazy" 
            style="width:100%;min-height:300px;border:0;border-radius:.5rem"></iframe>
  <?php elseif ($tileType === 'file'): ?>
    <?php if (!empty($tileText)): ?>
      <p class="mb-0 text-muted small"><?= esc($tileText) ?></p>
    <?php endif; ?>
  <?php endif; ?>
</div>
