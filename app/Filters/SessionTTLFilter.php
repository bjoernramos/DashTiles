<?php

namespace App\Filters;

use App\Models\UserSettingModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Ensures the per-user session duration is applied on every request.
 *
 * Behaviors:
 * - Sliding expiration: refreshes the session cookie TTL on each request using
 *   the user's configured `session_duration` (0 => session cookie).
 * - Cross-device update: periodically (every 60s) checks the current value in
 *   the database and updates the active session if it changed elsewhere.
 */
class SessionTTLFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId = (int) ($session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return;
        }

        // Current TTL in session (may be null on first request)
        $ttl = $session->get('session_duration');
        $ttl = is_numeric($ttl) ? (int) $ttl : null;

        // Periodically re-check DB to catch updates from other devices
        $now = time();
        $lastCheck = (int) ($session->get('session_duration_last_check') ?? 0);
        $mustCheckDb = ($now - $lastCheck) >= 60 || $ttl === null;
        if ($mustCheckDb) {
            try {
                $settings = (new UserSettingModel())->getOrCreate($userId);
                $dbTtl = (int) ($settings['session_duration'] ?? 7200);
                if ($ttl === null || $dbTtl !== $ttl) {
                    $ttl = max(0, $dbTtl);
                    $session->set('session_duration', $ttl);
                } else {
                    $ttl = max(0, $ttl);
                }
                $session->set('session_duration_last_check', $now);
            } catch (\Throwable $e) {
                // On error, keep existing TTL if available; otherwise default
                $ttl = $ttl ?? 7200;
            }
        } else {
            // Ensure non-negative
            $ttl = max(0, (int) $ttl);
        }

        // Refresh cookie expiration to implement sliding inactivity window
        try {
            $conf = config('Session');
            $cookieName = $conf->cookieName ?? 'ci_session';
            $sid = session_id();
            if ($sid !== '') {
                // ttl = 0 -> session cookie (expires on browser close)
                service('response')->setCookie(
                    $cookieName,
                    $sid,
                    $ttl,
                    '',
                    '',
                    $request->isSecure(),
                    true
                );
            }
        } catch (\Throwable $e) {
            // ignore cookie errors
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No-op
    }
}
