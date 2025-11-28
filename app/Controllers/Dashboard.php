<?php

namespace App\Controllers;

use App\Models\TileModel;
use App\Models\UserSettingModel;
use App\Models\UserModel;
use App\Models\GroupModel;
use App\Models\TileUserModel;
use App\Models\TileGroupModel;
use App\Models\UserTilePrefModel;

class Dashboard extends BaseController
{
    /**
     * Save uploaded image under public/uploads/tiles/{userId}/ and return relative path like uploads/tiles/{userId}/file.ext
     */
    private function saveUploadedImage(?\CodeIgniter\HTTP\Files\UploadedFile $file, int $userId): ?string
    {
        if (! $file || ! $file->isValid()) {
            return null;
        }
        // basic mime whitelist for images
        $mime = strtolower((string) $file->getMimeType());
        $allowed = ['image/png','image/jpeg','image/jpg','image/gif','image/webp','image/svg+xml'];
        if (! in_array($mime, $allowed, true)) {
            return null;
        }
        $publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
        $targetDir = $publicDir . 'uploads' . DIRECTORY_SEPARATOR . 'tiles' . DIRECTORY_SEPARATOR . $userId;
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        $newName = $file->getRandomName();
        $file->move($targetDir, $newName);
        return 'uploads/tiles/' . $userId . '/' . $newName;
    }

    private function deletePublicFileIfExists(?string $relPath): void
    {
        if (! $relPath) return;
        // only allow deleting within uploads/tiles
        if (strpos($relPath, 'uploads/tiles/') !== 0) return;
        $publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
        $abs = $publicDir . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    public function index()
    {
        $session = session();
        $userId = (int) $session->get('user_id');
        $role = (string) ($session->get('role') ?? 'user');
        if ($userId <= 0) {
            return redirect()->to('/login');
        }

        // Defensive: Falls Migrationen noch nicht gelaufen sind, zur Startseite mit Hinweis.
        try {
            $db = \Config\Database::connect();
            $hasSettings = $db->tableExists('user_settings');
            $hasTiles    = $db->tableExists('tiles');
        } catch (\Throwable $e) {
            $hasSettings = $hasTiles = false;
        }
        if (! $hasSettings || ! $hasTiles) {
            return redirect()->to('/')
                ->with('error', 'Die Dashboard-Tabellen fehlen. Bitte Migrationen ausführen (php spark migrate).');
        }

        $tilesModel = new TileModel();
        $settingsModel = new UserSettingModel();
        $settings = $settingsModel->getOrCreate($userId);
        $tiles = $tilesModel->forUser($userId)->findAll();

        // Zuordnungen für bestehende Tiles sammeln (für Vorbelegung in Edit-Formularen)
        $tileIds = array_map(static fn($t) => (int)$t['id'], $tiles);
        $tileUserMap = [];
        $tileGroupMap = [];
        if (!empty($tileIds)) {
            $tuRows = (new TileUserModel())->whereIn('tile_id', $tileIds)->findAll();
            foreach ($tuRows as $r) {
                $tid = (int)$r['tile_id'];
                $tileUserMap[$tid] = $tileUserMap[$tid] ?? [];
                $tileUserMap[$tid][] = (int)$r['user_id'];
            }
            $tgRows = (new TileGroupModel())->whereIn('tile_id', $tileIds)->findAll();
            foreach ($tgRows as $r) {
                $tid = (int)$r['tile_id'];
                $tileGroupMap[$tid] = $tileGroupMap[$tid] ?? [];
                $tileGroupMap[$tid][] = (int)$r['group_id'];
            }
        }

        // Für Auswahlfelder (Sichtbarkeit): alle Nutzer und Gruppen
        $users = (new UserModel())
            ->select('id, display_name, username')
            ->where('is_active', 1)
            ->orderBy('display_name', 'ASC')
            ->findAll();
        $groups = (new GroupModel())
            ->orderBy('name', 'ASC')
            ->findAll();

        // Versteckte globale Kacheln des Nutzers für Einstellungsbereich ermitteln
        $hiddenTiles = [];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('user_tile_prefs')) {
                $hiddenTiles = $db->table('tiles')
                    ->select('tiles.id, tiles.title, tiles.category')
                    ->join('user_tile_prefs utp', 'utp.tile_id = tiles.id', 'inner')
                    ->where('utp.user_id', $userId)
                    ->where('utp.hidden', 1)
                    ->where('tiles.is_global', 1)
                    ->where('tiles.deleted_at', null)
                    ->orderBy('tiles.category', 'ASC')
                    ->orderBy('tiles.title', 'ASC')
                    ->get()->getResultArray();
            }
        } catch (\Throwable $e) {
            $hiddenTiles = [];
        }

        // Group tiles by category
        $grouped = [];
        foreach ($tiles as $t) {
            $cat = $t['category'] ?: 'Allgemein';
            if (! isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $t;
        }

        return view('dashboard/index', [
            'columns'  => (int) ($settings['columns'] ?? 3),
            'tiles'    => $tiles,
            'grouped'  => $grouped,
            'userId'   => $userId,
            'role'     => $role,
            'backgroundEnabled' => (int)($settings['background_enabled'] ?? 0),
            'pingEnabled' => (int)($settings['ping_enabled'] ?? 1),
            'usersList'=> $users,
            'groupsList'=> $groups,
            'tileUserMap' => $tileUserMap,
            'tileGroupMap' => $tileGroupMap,
            'hiddenTiles' => $hiddenTiles,
        ]);
    }

    public function saveSettings()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to('/login');
        }
        $columns = (int) ($this->request->getPost('columns') ?? 3);
        if ($columns < 1) $columns = 1; if ($columns > 6) $columns = 6;

        $settings = new UserSettingModel();
        $existing = $settings->find($userId);
        if ($existing) {
            $settings->update($userId, ['columns' => $columns]);
        } else {
            $settings->insert(['user_id' => $userId, 'columns' => $columns]);
        }
        return redirect()->to('/dashboard')->with('success', 'Layout gespeichert');
    }

    public function store()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to('/login');
        }

        $type = (string) $this->request->getPost('type');
        $title = trim((string) $this->request->getPost('title'));
        $url = null;

        if ($type === 'file') {
            $file = $this->request->getFile('file');
            if ($file && $file->isValid()) {
                $writable = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR;
                $targetDir = $writable . 'uploads' . DIRECTORY_SEPARATOR . $userId;
                if (! is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                $newName = $file->getRandomName();
                $file->move($targetDir, $newName);
                // store relative path from writable
                $url = 'uploads/' . $userId . '/' . $newName;
            }
        } else {
            $url = trim((string) $this->request->getPost('url')) ?: null;
        }

        $isGlobalInput = $this->request->getPost('is_global');
        $isAdmin = session()->get('role') === 'admin';
        $data = [
            'user_id'  => $userId,
            'type'     => in_array($type, ['link','iframe','file'], true) ? $type : 'link',
            'title'    => $title,
            'url'      => $url,
            'icon'     => trim((string) $this->request->getPost('icon')) ?: null,
            // background color can be a simple color or a CSS gradient string
            // Prefer explicit text field (gradient) over picker when both provided
            'bg_color' => (function($req){
                $txt = $req->getPost('bg_color');
                $pick = $req->getPost('bg_color_picker');
                $pickUsed = $req->getPost('bg_color_picker_used');
                $txt = trim((string)($txt ?? ''));
                $pick = trim((string)($pick ?? ''));
                if ($txt !== '') return $txt;
                if (($pickUsed === '1' || $pickUsed === 1) && $pick !== '') return $pick;
                return null;
            })($this->request),
            'text'     => trim((string) $this->request->getPost('text')) ?: null,
            'category' => trim((string) $this->request->getPost('category')) ?: null,
        ];

        // handle optional uploaded icon/background
        try {
            $iconFile = $this->request->getFile('icon_file');
            $bgFile   = $this->request->getFile('bg_file');
            $iconPath = $this->saveUploadedImage($iconFile, $userId);
            $bgPath   = $this->saveUploadedImage($bgFile, $userId);
            if ($iconPath) { $data['icon_path'] = $iconPath; }
            if ($bgPath)   { $data['bg_path']   = $bgPath; }
        } catch (\Throwable $e) {
            // ignore upload errors silently; user can retry
            log_message('warning', 'Icon/BG upload failed on create: {msg}', ['msg' => $e->getMessage()]);
        }

        // Determine next position automatically within the category for this user
        try {
            $pos = (new TileModel())->nextPositionForUserCategory($userId, $data['category'] ?? null);
            $data['position'] = $pos;
        } catch (\Throwable $e) {
            // fallback to zero, but keep saving; log the error
            log_message('error', 'Failed to compute next position: {msg}', ['msg' => $e->getMessage()]);
            $data['position'] = 0;
        }

        // Admins dürfen eine Kachel als global markieren
        if ($isAdmin && ($isGlobalInput === '1' || $isGlobalInput === 1)) {
            $data['is_global'] = 1;
        }

        $model = new TileModel();
        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('error', implode("\n", $model->errors() ?: ['Speichern fehlgeschlagen']));
        }
        $tileId = (int) $model->getInsertID();

        // Sichtbarkeits-Zuweisungen speichern (optional)
        $this->saveTileAssignments($tileId);

        return redirect()->to('/dashboard')->with('success', 'Kachel angelegt');
    }

    public function update(int $id)
    {
        $userId = (int) session()->get('user_id');
        $model = new TileModel();
        $tile = $model->find($id);
        $isAdmin = session()->get('role') === 'admin';
        $isOwner = $tile && (int) $tile['user_id'] === $userId;
        $isGlobal = $tile && (int) ($tile['is_global'] ?? 0) === 1;
        if (! $tile || (! $isOwner && ! ($isAdmin && $isGlobal))) {
            return redirect()->to('/dashboard')->with('error', 'Kachel nicht gefunden');
        }

        $type = (string) $this->request->getPost('type') ?: $tile['type'];
        $url = $tile['url'];
        if ($type === 'file') {
            $file = $this->request->getFile('file');
            if ($file && $file->isValid()) {
                $writable = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR;
                $targetDir = $writable . 'uploads' . DIRECTORY_SEPARATOR . $userId;
                if (! is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                $newName = $file->getRandomName();
                $file->move($targetDir, $newName);
                $url = 'uploads/' . $userId . '/' . $newName;
            }
        } else {
            $url = trim((string) $this->request->getPost('url')) ?: $url;
        }

        // Special handling for text: allow clearing the field (set to NULL) when user submits an empty value
        $newText = $tile['text'];
        $textPost = $this->request->getPost('text');
        if ($textPost !== null) {
            $t = trim((string) $textPost);
            $newText = ($t === '') ? null : $t;
        }

        $data = [
            // include user_id to satisfy model validation rules on update
            'user_id'  => (int) ($tile['user_id'] ?? $userId),
            'type'     => in_array($type, ['link','iframe','file'], true) ? $type : $tile['type'],
            'title'    => trim((string) $this->request->getPost('title')) ?: $tile['title'],
            'url'      => $url,
            'icon'     => trim((string) $this->request->getPost('icon')) ?: $tile['icon'],
            'text'     => $newText,
            'category' => trim((string) $this->request->getPost('category')) ?: $tile['category'],
        ];

        // Do not let users set arbitrary positions via the edit form; keep current backend-managed position
        $data['position'] = (int) ($tile['position'] ?? 0);

        // Nur Admins dürfen die Global-Markierung setzen/entfernen
        if ($isAdmin) {
            $isGlobalInput = $this->request->getPost('is_global');
            $data['is_global'] = ($isGlobalInput === '1' || $isGlobalInput === 1) ? 1 : 0;
        }

        // Optional: delete existing icon/background
        $deleteIcon = $this->request->getPost('delete_icon');
        $deleteBg   = $this->request->getPost('delete_bg');
        $deleteBgColor = $this->request->getPost('delete_bg_color');
        if ($deleteIcon) {
            $this->deletePublicFileIfExists($tile['icon_path'] ?? null);
            $data['icon_path'] = null;
        }
        if ($deleteBg) {
            $this->deletePublicFileIfExists($tile['bg_path'] ?? null);
            $data['bg_path'] = null;
        }
        if ($deleteBgColor) {
            $data['bg_color'] = null;
        }

        // Handle new uploads (replace existing if present)
        try {
            $iconFile = $this->request->getFile('icon_file');
            if ($iconFile && $iconFile->isValid()) {
                $newIcon = $this->saveUploadedImage($iconFile, $userId);
                if ($newIcon) {
                    // remove old
                    if (!empty($tile['icon_path'])) { $this->deletePublicFileIfExists($tile['icon_path']); }
                    $data['icon_path'] = $newIcon;
                }
            }
            $bgFile = $this->request->getFile('bg_file');
            if ($bgFile && $bgFile->isValid()) {
                $newBg = $this->saveUploadedImage($bgFile, $userId);
                if ($newBg) {
                    if (!empty($tile['bg_path'])) { $this->deletePublicFileIfExists($tile['bg_path']); }
                    $data['bg_path'] = $newBg;
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Icon/BG upload failed on update: {msg}', ['msg' => $e->getMessage()]);
        }

        // Handle background color update/clear
        $bgTxt = $this->request->getPost('bg_color');
        $bgPick = $this->request->getPost('bg_color_picker');
        $bgPickUsed = $this->request->getPost('bg_color_picker_used');
        $bgTouch = $this->request->getPost('bg_color_touch');
        if ($bgTouch === '1') {
            $txt = trim((string)($bgTxt ?? ''));
            $pick = trim((string)($bgPick ?? ''));
            if ($txt !== '') {
                $data['bg_color'] = $txt;
            } elseif (($bgPickUsed === '1' || $bgPickUsed === 1) && $pick !== '') {
                $data['bg_color'] = $pick;
            } else {
                $data['bg_color'] = null; // explicit clear when both empty
            }
        }

        if (! $model->update($id, $data)) {
            return redirect()->back()->withInput()->with('error', implode("\n", $model->errors() ?: ['Aktualisierung fehlgeschlagen']));
        }
        // Aktualisiere Zuweisungen
        $this->saveTileAssignments($id, true);
        return redirect()->to('/dashboard')->with('success', 'Kachel aktualisiert');
    }

    public function delete(int $id)
    {
        $userId = (int) session()->get('user_id');
        $model = new TileModel();
        $tile = $model->find($id);
        $isAdmin = session()->get('role') === 'admin';
        $isOwner = $tile && (int) $tile['user_id'] === $userId;
        $isGlobal = $tile && (int) ($tile['is_global'] ?? 0) === 1;
        if (! $tile || (! $isOwner && ! ($isAdmin && $isGlobal))) {
            return redirect()->to('/dashboard')->with('error', 'Kachel nicht gefunden');
        }
        // Pivots entfernen
        (new TileUserModel())->where('tile_id', $id)->delete();
        (new TileGroupModel())->where('tile_id', $id)->delete();
        // delete media files
        $this->deletePublicFileIfExists($tile['icon_path'] ?? null);
        $this->deletePublicFileIfExists($tile['bg_path'] ?? null);
        $model->delete($id);
        return redirect()->to('/dashboard')->with('success', 'Kachel gelöscht');
    }

    /**
     * Speichert neue Reihenfolge der Kacheln innerhalb einer Kategorie pro Nutzer
     * Erwartet POST: category (string), ids[] (tile ids in neuer Reihenfolge)
     */
    public function reorder()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false]);
        }
        $category = (string) ($this->request->getPost('category') ?? '');
        $ids = $this->request->getPost('ids');
        if (!is_array($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid ids']);
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));

        // Defensive: Prüfen, ob Tabelle existiert
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('user_tile_prefs')) {
                return $this->response->setStatusCode(503)->setJSON(['ok' => false, 'error' => 'Prefs table missing, run migrations']);
            }
        } catch (\Throwable $e) {
            log_message('error', 'DB connection failed in reorder: {msg}', ['msg' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'error' => 'DB error']);
        }

        // Nur Kacheln berücksichtigen, die der Nutzer sehen darf
        $visibleIds = array_map(static fn($r) => (int)$r['id'], (new TileModel())->forUser($userId)->findAll());
        $visibleSet = array_flip($visibleIds);

        $model = new UserTilePrefModel();
        $now = date('Y-m-d H:i:s');

        // Transaktion zur Vermeidung von race conditions
        $db = \Config\Database::connect();
        $prefsTable = $db->table('user_tile_prefs');
        $db->transStart();
        try {
            $pos = 0;
            foreach ($ids as $tid) {
                if ($tid <= 0) continue;
                if (!isset($visibleSet[$tid])) continue;
                $existing = $model->where('user_id', $userId)->where('tile_id', $tid)->first();
                $row = [
                    'user_id' => $userId,
                    'tile_id' => $tid,
                    'position' => $pos,
                    'updated_at' => $now,
                ];
                if ($existing) {
                    // Query Builder verwenden, da das Model keinen Primary Key hat
                    $prefsTable->where('user_id', $userId)->where('tile_id', $tid)->update($row);
                } else {
                    $row['hidden'] = 0;
                    // Insert über Builder, um PK-Einschränkungen des Models zu vermeiden
                    $prefsTable->insert($row);
                }
                $pos++;
            }
            $db->transComplete();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Reorder failed for user {uid}: {msg}', ['uid' => $userId, 'msg' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'error' => 'Reorder failed']);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /** Markiert eine globale Kachel für den aktuellen Nutzer als versteckt */
    public function hide(int $id)
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to('/login');
        }
        // Tile prüfen
        $tile = (new TileModel())->find($id);
        if (! $tile || (int)($tile['is_global'] ?? 0) !== 1) {
            return redirect()->to('/dashboard')->with('error', 'Kachel nicht gefunden');
        }

        // Defensive: Prüfen, ob Tabelle für Benutzerpräferenzen existiert
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('user_tile_prefs')) {
                return redirect()->to('/dashboard')->with('error', 'Funktion nicht verfügbar. Bitte Migrationen ausführen (php spark migrate).');
            }
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', 'Interner Fehler (DB-Verbindung).');
        }

        try {
            $model = new UserTilePrefModel();
            $db = \Config\Database::connect();
            $prefs = $db->table('user_tile_prefs');
            $existing = $model->where('user_id', $userId)->where('tile_id', $id)->first();
            $row = [ 'user_id' => $userId, 'tile_id' => $id, 'hidden' => 1, 'updated_at' => date('Y-m-d H:i:s') ];
            if ($existing) {
                $prefs->where('user_id', $userId)->where('tile_id', $id)->update($row);
            } else {
                $prefs->insert($row);
            }
            return redirect()->to('/dashboard')->with('success', 'Kachel für dich ausgeblendet');
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', 'Speichern fehlgeschlagen.');
        }
    }

    /** Hebt das Verstecken einer globalen Kachel für den aktuellen Nutzer auf */
    public function unhide(int $id)
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to('/login');
        }
        $tile = (new TileModel())->find($id);
        if (! $tile || (int)($tile['is_global'] ?? 0) !== 1) {
            return redirect()->to('/dashboard')->with('error', 'Kachel nicht gefunden');
        }

        // Defensive: Prüfen, ob Tabelle existiert
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('user_tile_prefs')) {
                return redirect()->to('/dashboard')->with('error', 'Funktion nicht verfügbar. Bitte Migrationen ausführen (php spark migrate).');
            }
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', 'Interner Fehler (DB-Verbindung).');
        }

        try {
            $model = new UserTilePrefModel();
            $db = \Config\Database::connect();
            $prefs = $db->table('user_tile_prefs');
            $existing = $model->where('user_id', $userId)->where('tile_id', $id)->first();
            if ($existing) {
                $prefs->where('user_id', $userId)->where('tile_id', $id)->update(['hidden' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
            }
            return redirect()->to('/dashboard')->with('success', 'Kachel wieder eingeblendet');
        } catch (\Throwable $e) {
            return redirect()->to('/dashboard')->with('error', 'Speichern fehlgeschlagen.');
        }
    }

    public function file(int $id)
    {
        $userId = (int) session()->get('user_id');
        $model = new TileModel();
        $tile = $model->find($id);
        // Zugriff erlaubt: Besitzer oder (global & Datei) für alle Nutzer
        $isGlobal = $tile && (int) ($tile['is_global'] ?? 0) === 1;
        if (! $tile || (! $isGlobal && (int) $tile['user_id'] !== $userId) || $tile['type'] !== 'file' || empty($tile['url'])) {
            return $this->response->setStatusCode(404, 'Not found');
        }
        $relative = $tile['url']; // e.g., uploads/123/filename.ext
        $writable = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR;
        $fullPath = $writable . str_replace(['..', '//', '\\'], ['', '/', '/'], $relative);
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404, 'File missing');
        }
        // Let browser decide to open or download based on mime
        return $this->response->download($fullPath, null)->setFileName(basename($fullPath));
    }

    /**
     * Speichert Benutzer- und Gruppen-Zuweisungen für eine Kachel
     * @param int $tileId
     * @param bool $replace Wenn true, bestehende Zuweisungen werden ersetzt
     */
    private function saveTileAssignments(int $tileId, bool $replace = false): void
    {
        $userIds = $this->request->getPost('visible_user_ids');
        $groupIds = $this->request->getPost('visible_group_ids');

        $userIds = is_array($userIds) ? array_values(array_unique(array_map('intval', $userIds))) : [];
        $groupIds = is_array($groupIds) ? array_values(array_unique(array_map('intval', $groupIds))) : [];

        $tu = new TileUserModel();
        $tg = new TileGroupModel();

        if ($replace) {
            $tu->where('tile_id', $tileId)->delete();
            $tg->where('tile_id', $tileId)->delete();
        }

        foreach ($userIds as $uid) {
            if ($uid > 0) {
                $tu->insert(['tile_id' => $tileId, 'user_id' => $uid]);
            }
        }
        foreach ($groupIds as $gid) {
            if ($gid > 0) {
                $tg->insert(['tile_id' => $tileId, 'group_id' => $gid]);
            }
        }
    }
}
