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
            'text'     => trim((string) $this->request->getPost('text')) ?: null,
            'category' => trim((string) $this->request->getPost('category')) ?: null,
            'position' => (int) ($this->request->getPost('position') ?? 0),
        ];

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

        $data = [
            'type'     => in_array($type, ['link','iframe','file'], true) ? $type : $tile['type'],
            'title'    => trim((string) $this->request->getPost('title')) ?: $tile['title'],
            'url'      => $url,
            'icon'     => trim((string) $this->request->getPost('icon')) ?: $tile['icon'],
            'text'     => trim((string) $this->request->getPost('text')) ?: $tile['text'],
            'category' => trim((string) $this->request->getPost('category')) ?: $tile['category'],
            'position' => (int) ($this->request->getPost('position') ?? $tile['position']),
        ];

        // Nur Admins dürfen die Global-Markierung setzen/entfernen
        if ($isAdmin) {
            $isGlobalInput = $this->request->getPost('is_global');
            $data['is_global'] = ($isGlobalInput === '1' || $isGlobalInput === 1) ? 1 : 0;
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
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));

        // Optional: Nur Kacheln berücksichtigen, die der Nutzer sehen darf
        $visibleIds = array_map(static fn($r) => (int)$r['id'], (new TileModel())->forUser($userId)->findAll());
        $visibleSet = array_flip($visibleIds);

        $model = new UserTilePrefModel();
        $now = date('Y-m-d H:i:s');
        $pos = 0;
        foreach ($ids as $tid) {
            if ($tid <= 0) continue;
            if (!isset($visibleSet[$tid])) continue;
            // Upsert: vorhandenen Datensatz aktualisieren, sonst neu anlegen
            $existing = $model->where('user_id', $userId)->where('tile_id', $tid)->first();
            $row = [
                'user_id' => $userId,
                'tile_id' => $tid,
                'position' => $pos,
                'updated_at' => $now,
            ];
            if ($existing) {
                $model->where('user_id', $userId)->where('tile_id', $tid)->set($row)->update();
            } else {
                $row['hidden'] = 0;
                $model->insert($row);
            }
            $pos++;
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
        $tile = (new TileModel())->find($id);
        if (! $tile || (int)($tile['is_global'] ?? 0) !== 1) {
            return redirect()->to('/dashboard')->with('error', 'Kachel nicht gefunden');
        }
        $model = new UserTilePrefModel();
        $existing = $model->where('user_id', $userId)->where('tile_id', $id)->first();
        $row = [ 'user_id' => $userId, 'tile_id' => $id, 'hidden' => 1, 'updated_at' => date('Y-m-d H:i:s') ];
        if ($existing) {
            $model->where('user_id', $userId)->where('tile_id', $id)->set($row)->update();
        } else {
            $model->insert($row);
        }
        return redirect()->to('/dashboard')->with('success', 'Kachel für dich ausgeblendet');
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
        $model = new UserTilePrefModel();
        $existing = $model->where('user_id', $userId)->where('tile_id', $id)->first();
        if ($existing) {
            $model->where('user_id', $userId)->where('tile_id', $id)->set(['hidden' => 0, 'updated_at' => date('Y-m-d H:i:s')])->update();
        }
        return redirect()->to('/dashboard')->with('success', 'Kachel wieder eingeblendet');
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
