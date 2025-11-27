<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class PluginsController extends BaseController
{
    /**
     * List installed plugins by scanning plugins/{id}/plugin.json
     */
    public function index(): ResponseInterface
    {
        $pluginsDir = rtrim(ROOTPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugins';
        $result = [];

        if (is_dir($pluginsDir)) {
            $dh = opendir($pluginsDir);
            if ($dh) {
                while (($entry = readdir($dh)) !== false) {
                    if ($entry === '.' || $entry === '..') continue;
                    // only simple ids
                    if (!preg_match('/^[a-z0-9_\-]+$/', $entry)) continue;
                    $manifestPath = $pluginsDir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'plugin.json';
                    if (!is_file($manifestPath)) continue;
                    $json = @file_get_contents($manifestPath);
                    $manifest = json_decode($json ?: '', true);
                    if (!is_array($manifest)) continue;

                    $basePath = rtrim((string)(getenv('toolpages.basePath') ?: '/'), '/');
                    $manifestUrl = ($basePath === '' ? '' : $basePath) . '/plugins/' . $entry . '/plugin.json';

                    $result[] = [
                        'id' => (string)($manifest['id'] ?? $entry),
                        'name' => (string)($manifest['name'] ?? $entry),
                        'version' => (string)($manifest['version'] ?? ''),
                        'tiles' => $manifest['tiles'] ?? [],
                        'manifestUrl' => $manifestUrl,
                    ];
                }
                closedir($dh);
            }
        }

        return $this->response->setJSON($result);
    }

    /**
     * Serve the plugin.json for a given plugin id.
     */
    public function manifest(string $id)
    {
        if (!$this->isValidPluginId($id)) {
            return $this->response->setStatusCode(400)->setBody('invalid plugin id');
        }
        $file = rtrim(ROOTPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'plugin.json';
        if (!is_file($file)) {
            return $this->response->setStatusCode(404)->setBody('not found');
        }
        $this->setCacheHeaders($file);
        return $this->response->setContentType('application/json')->setBody(file_get_contents($file));
    }

    /**
     * Serve static files under plugins/{id}/web/* with traversal protection.
     */
    public function web(string $id, string $path)
    {
        if (!$this->isValidPluginId($id)) {
            return $this->response->setStatusCode(400)->setBody('invalid plugin id');
        }

        // Robust: always reconstruct path after /plugins/{id}/web/ from the actual request URI
        $uriPath = ltrim((string) $this->request->getUri()->getPath(), '/');
        $expectedPrefix = 'plugins/' . $id . '/web/';
        if (strpos($uriPath, $expectedPrefix) === 0) {
            $reconstructed = substr($uriPath, strlen($expectedPrefix));
            if ($reconstructed !== '') {
                $path = $reconstructed;
            }
        }

        // Normalize and block traversal
        $path = str_replace(['\\'], '/', $path);
        if ($path === '' || strpos($path, '..') !== false) {
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                log_message('debug', '[plugins] invalid path provided: {path}', ['path' => $path]);
            }
            return $this->response->setStatusCode(400)->setBody('invalid path');
        }

        $base = rtrim(ROOTPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'web';
        $target = $base . DIRECTORY_SEPARATOR . $path;

        $realBase = realpath($base) ?: $base;
        $realTarget = realpath($target);

        // Dev logging to diagnose path issues (e.g., when some files work and others 404)
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            log_message('debug', '[plugins] serve web asset: id={id} uriPath={uriPath} path={path} base={base} target={target} realBase={realBase} realTarget={realTarget}', [
                'id' => $id,
                'uriPath' => $uriPath,
                'path' => $path,
                'base' => $base,
                'target' => $target,
                'realBase' => $realBase,
                'realTarget' => $realTarget ?: '(false)'
            ]);
        }

        if ($realTarget === false || strpos($realTarget, $realBase) !== 0) {
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                log_message('debug', '[plugins] 404: realTarget invalid or outside base (id={id})', [
                    'id' => $id,
                ]);
            }
            return $this->response->setStatusCode(404)->setBody('not found');
        }
        if (!is_file($realTarget) || !is_readable($realTarget)) {
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                log_message('debug', '[plugins] 404: not a file or unreadable: {file}', ['file' => $realTarget]);
            }
            return $this->response->setStatusCode(404)->setBody('not found');
        }

        $this->setCacheHeaders($realTarget);
        $mime = $this->guessMimeType($realTarget);
        return $this->response->setContentType($mime)->setBody(file_get_contents($realTarget));
    }

    /**
     * Server-side fetch proxy for the RSS Reader plugin.
     * POST body JSON: { url: string }
     * Returns JSON: { ok: true, xml: string } or { ok: false, error: string }
     *
     * Security notes:
     * - Only http/https URLs
     * - Optional allowlist via env PLUGINS_RSS_READER_ALLOWLIST (comma-separated hostnames or origins)
     * - Timeout and size limit
     * - Only XML-like content-types are accepted
     */
    public function proxyRss()
    {
        try {
            $req = $this->request->getJSON(true);
        } catch (\Throwable $e) {
            $req = null;
        }
        $url = is_array($req) ? (string)($req['url'] ?? '') : '';
        $url = trim($url);
        if ($url === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'url required']);
        }

        // Basic URL validation
        if (!preg_match('#^https?://#i', $url)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'only http(s) allowed']);
        }

        // Optional allowlist by env: comma-separated list of hosts or full origins
        $allowEnv = (string) (getenv('PLUGINS_RSS_READER_ALLOWLIST') ?: '');
        if ($allowEnv !== '') {
            $allowed = array_filter(array_map('trim', explode(',', $allowEnv)));
            if (!empty($allowed)) {
                $parsed = parse_url($url);
                $host = (string) ($parsed['host'] ?? '');
                $origin = '';
                if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
                    $origin = strtolower($parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? (':' . $parsed['port']) : ''));
                }
                $ok = false;
                foreach ($allowed as $entry) {
                    $entry = strtolower($entry);
                    if ($entry === $host || $entry === $origin) { $ok = true; break; }
                    // Support wildcard prefix like *.example.com
                    if (str_starts_with($entry, '*.') && $host !== '') {
                        $suffix = substr($entry, 1); // "*.example.com" -> ".example.com"
                        if ($suffix && str_ends_with('.' . $host, $suffix)) { $ok = true; break; }
                    }
                }
                if (!$ok) {
                    return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'origin not allowed']);
                }
            }
        }

        // Perform server-side request
        try {
            /** @var \CodeIgniter\HTTP\CURLRequest $client */
            $client = \Config\Services::curlrequest();
            $resp = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/rss+xml, application/atom+xml, text/xml;q=0.9, */*;q=0.8',
                    'User-Agent' => 'DashTiles RSS Proxy/1.0'
                ],
                'timeout' => 8,
                'allow_redirects' => [ 'max' => 3 ],
                // Limit downloaded size to ~2.5MB by using sink callback
                'http_errors' => false,
            ]);

            $status = $resp->getStatusCode();
            if ($status < 200 || $status >= 300) {
                return $this->response->setStatusCode(502)->setJSON(['ok' => false, 'error' => 'upstream status ' . $status]);
            }

            $ctype = (string) $resp->getHeaderLine('Content-Type');
            $body = (string) $resp->getBody();

            // Enforce size limit ~2.5MB
            if (strlen($body) > 2_500_000) {
                return $this->response->setStatusCode(413)->setJSON(['ok' => false, 'error' => 'feed too large']);
            }

            // Minimal content-type check: allow XML-ish or text/plain (some feeds)
            $okType = false;
            $lower = strtolower($ctype);
            foreach (['xml', 'rss', 'atom', 'text/plain'] as $needle) {
                if (strpos($lower, $needle) !== false) { $okType = true; break; }
            }
            if (!$okType) {
                // Still try to detect if content looks like XML
                if (!(str_starts_with(ltrim($body), '<'))) {
                    return $this->response->setStatusCode(415)->setJSON(['ok' => false, 'error' => 'unsupported content-type']);
                }
            }

            // Return XML as text inside JSON to avoid content-type/CORS issues
            return $this->response->setJSON(['ok' => true, 'xml' => $body]);
        } catch (\Throwable $e) {
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                log_message('debug', '[plugins][rss] proxy error: {msg}', ['msg' => $e->getMessage()]);
            }
            return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'error' => 'proxy error']);
        }
    }

    private function isValidPluginId(string $id): bool
    {
        return (bool) preg_match('/^[a-z0-9_\-]+$/', $id);
    }

    private function guessMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'js' => 'text/javascript; charset=UTF-8',
            'mjs' => 'text/javascript; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'json' => 'application/json; charset=UTF-8',
            'html' => 'text/html; charset=UTF-8',
            'txt' => 'text/plain; charset=UTF-8',
            'woff2' => 'font/woff2',
        ];
        if (isset($map[$ext])) return $map[$ext];
        $detected = function_exists('mime_content_type') ? @mime_content_type($path) : null;
        return $detected ?: 'application/octet-stream';
    }

    private function setCacheHeaders(string $file): void
    {
        $lastMod = gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT';
        $etag = 'W/"' . md5($file . '|' . filesize($file) . '|' . filemtime($file)) . '"';
        $this->response->setHeader('Last-Modified', $lastMod);
        $this->response->setHeader('ETag', $etag);
        $this->response->setHeader('Cache-Control', 'public, max-age=3600');
    }
}
