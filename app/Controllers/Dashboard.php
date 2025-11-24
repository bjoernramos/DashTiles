<?php

namespace App\Controllers;

use App\Models\TileModel;
use App\Models\UserSettingModel;

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
        $model->delete($id);
        return redirect()->to('/dashboard')->with('success', 'Kachel gelöscht');
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
}
