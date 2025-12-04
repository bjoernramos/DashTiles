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
                $data['settings'] = $settings;
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

    /**
     * Same-origin ping endpoint to check reachability of arbitrary http/https URLs
     * without triggering browser CORS/CORP errors. Requires an authenticated session.
     * Query param: u=<url>
     */
    public function ping()
    {
        // Require login to avoid exposing as open proxy
        if (! session()->get('user_id')) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'error' => 'unauthorized']);
        }

        $url = (string) ($this->request->getGet('u') ?? '');
        $url = trim($url);
        if ($url === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'missing url']);
        }

        // Basic validation: must be http/https
        $parts = @parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http','https'], true) || empty($parts['host'])) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'invalid url']);
        }

        $host = $parts['host'];
        // Block obvious local targets
        $blockedHosts = ['localhost', '127.0.0.1', '::1'];
        if (in_array($host, $blockedHosts, true)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'blocked host']);
        }

        // Resolve host to IP and block private ranges (best-effort)
        $ip = @gethostbyname($host);
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            if (
                // RFC1918 IPv4
                preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $ip) ||
                // Loopback
                preg_match('/^(127\.)/', $ip)
            ) {
                return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'blocked ip']);
            }
        }

        // Structured logging: start ping
        $userId = (int) session()->get('user_id');
        $reqId = bin2hex(random_bytes(8));
        $clientIp = (string) ($this->request->getIPAddress() ?? '');
        log_message('info', 'PING start req={req} user={user} ip={ip} url="{url}" host={host}', [
            'req'  => $reqId,
            'user' => $userId,
            'ip'   => $clientIp,
            'url'  => $url,
            'host' => $host,
        ]);

        $client = \Config\Services::curlrequest([
            'http_errors' => false,
            'allow_redirects' => ['max' => 3, 'strict' => false, 'referer' => false],
            'connect_timeout' => 3.0,
            'timeout' => 5.0,
            'verify' => false, // in internal networks self-signed may exist; set true if you require TLS verify
        ]);

        $start = microtime(true);
        try {
            // Many CDNs block HEAD or Range requests. Prefer a simple GET first with a
            // browser-like User-Agent to improve acceptance.
            $resp = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; ToolpagesPing/1.0; +https://example.invalid) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
            ]);
            $code = (int) $resp->getStatusCode();
            // Optional fallback: try HEAD if GET yielded no response code
            if ($code === 0) {
                $resp = $client->request('HEAD', $url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (compatible; ToolpagesPing/1.0)'
                    ],
                ]);
                $code = (int) $resp->getStatusCode();
            }
            $ms = (int) round((microtime(true) - $start) * 1000);
            $ok = ($code > 0 && $code < 400);
            $payload = ['ok' => $ok, 'status' => $code, 'ms' => $ms];

            log_message($ok ? 'info' : 'warning', 'PING done req={req} user={user} status={status} ms={ms} url="{url}"', [
                'req'    => $reqId,
                'user'   => $userId,
                'status' => $code,
                'ms'     => $ms,
                'url'    => $url,
            ]);
            return $this->response->setStatusCode($ok ? 200 : 502)->setJSON($payload);
        } catch (\Throwable $e) {
            $ms = (int) round((microtime(true) - $start) * 1000);
            log_message('error', 'PING fail req={req} user={user} ms={ms} url="{url}" err={err}', [
                'req'  => $reqId,
                'user' => $userId,
                'ms'   => $ms,
                'url'  => $url,
                'err'  => $e->getMessage(),
            ]);
            return $this->response->setStatusCode(502)->setJSON(['ok' => false, 'error' => 'request_failed', 'ms' => $ms]);
        }
    }
}
