<?php

namespace App\Controllers;

use App\Models\TileModel;
use App\Models\UserSettingModel;

class Home extends BaseController
{
    public function index()
    {
        $basePath = rtrim((string) (getenv('toolpages.basePath') ?: '/'), '/');

        $data = [
            'basePath' => $basePath,
        ];

        // Wenn eingeloggt: Kacheln und Layout laden und direkt auf der Startseite anzeigen
        if (session()->get('user_id')) {
            // Defensive: Falls Migrationen für Tiles/Settings noch nicht ausgeführt wurden,
            // vermeiden wir einen 500-Fehler und zeigen stattdessen einen Hinweis an.
            try {
                $db = \Config\Database::connect();
                $hasSettings = $db->tableExists('user_settings');
                $hasTiles    = $db->tableExists('tiles');
            } catch (\Throwable $e) {
                $hasSettings = $hasTiles = false;
            }

            if ($hasSettings && $hasTiles) {
                $userId = (int) session()->get('user_id');
                $tilesModel = new TileModel();
                $settingsModel = new UserSettingModel();
                $settings = $settingsModel->getOrCreate($userId);
                $tiles = $tilesModel->forUser($userId)->findAll();

                // Nach Kategorien gruppieren
                $grouped = [];
                foreach ($tiles as $t) {
                    $cat = $t['category'] ?: 'Allgemein';
                    if (! isset($grouped[$cat])) {
                        $grouped[$cat] = [];
                    }
                    $grouped[$cat][] = $t;
                }

                $data['columns'] = (int) ($settings['columns'] ?? 3);
                $data['tiles']   = $tiles;
                $data['grouped'] = $grouped;
            } else {
                $data['tiles_error'] = 'Die Dashboard-Tabellen fehlen. Bitte Migrationen ausführen (php spark migrate).';
            }
        }

        return view('home', $data);
    }

    public function health()
    {
        return $this->response->setJSON([
            'status' => 'ok',
            'time' => date(DATE_ATOM),
        ]);
    }
}
