<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\LDAPService;
use App\Models\UserSettingModel;

class AuthController extends BaseController
{
    public function showLogin()
    {
        $basePath = rtrim((string) (getenv('toolpages.basePath') ?: '/toolpages'), '/');
        return view('auth/login', ['basePath' => $basePath]);
    }

    public function postLocal()
    {
        $username = (string) ($this->request->getPost('username') ?? '');
        $password = (string) ($this->request->getPost('password') ?? '');

        $userModel = new UserModel();
        $user = $userModel->where('auth_source', 'local')->where('is_active', 1)->findByUsername($username);
        if (! $user || empty($user['password_hash']) || ! password_verify($password, $user['password_hash'])) {
            return redirect()->to('/login')->with('error', 'Invalid credentials');
        }

        $this->setSessionForUser($user);
        return redirect()->to('/');
    }

    public function postLdap()
    {
        $username = (string) ($this->request->getPost('username') ?? '');
        $password = (string) ($this->request->getPost('password') ?? '');

        if ($username === '' || $password === '') {
            return redirect()->to('/login')->with('error', 'Username and password required');
        }

        $ldap = new LDAPService();
        try {
            $entry = $ldap->findUserByUid($username);
            if (! $entry || empty($entry['dn'])) {
                return redirect()->to('/login')->with('error', 'User not found in LDAP');
            }
            $ok = $ldap->authenticate($entry['dn'], $password);
            if (! $ok) {
                return redirect()->to('/login')->with('error', 'Invalid LDAP credentials');
            }
            // Optional group membership enforcement via ldap.groupFilter (from .env)
            if (! $ldap->isMemberOfRequiredGroup($entry)) {
                return redirect()->to('/login')->with('error', 'Access denied: not a member of required group');
            }
        } catch (\Throwable $e) {
            return redirect()->to('/login')->with('error', 'LDAP error: ' . $e->getMessage());
        }

        $userModel = new UserModel();
        $user = $userModel->findByUsername($username);

        // Helper: extract fields from LDAP entry
        $ldapDisplay = $entry['displayName'] ?? ($entry['cn'] ?? null);
        $ldapFirst   = $entry['givenName'] ?? null;
        $ldapLast    = $entry['sn'] ?? null;
        $ldapMail    = $entry['mail'] ?? null;
        $ldapPhone   = $entry['telephoneNumber'] ?? null;
        // Build a simple address string if components exist
        $street      = $entry['streetAddress'] ?? ($entry['street'] ?? null);
        $city        = $entry['l'] ?? null;
        $postal      = $entry['postalCode'] ?? null;
        $ldapAddress = null;
        if ($street || $postal || $city) {
            $parts = [];
            if ($street) { $parts[] = $street; }
            $pcCity = trim(($postal ? ($postal . ' ') : '') . ($city ?: ''));
            if ($pcCity !== '') { $parts[] = $pcCity; }
            $ldapAddress = implode(', ', $parts);
        }

        if (! $user) {
            // Auto-create
            $userId = $userModel->insert([
                'username'      => $entry['uid'] ?? $username,
                'display_name'  => $ldapDisplay,
                'email'         => $ldapMail,
                'auth_source'   => 'ldap',
                'password_hash' => null,
                'ldap_dn'       => $entry['dn'] ?? null,
                'role'          => 'user',
                'is_active'     => 1,
                // Optional profile fields
                'first_name'    => $ldapFirst,
                'last_name'     => $ldapLast,
                'phone'         => $ldapPhone,
                'address'       => $ldapAddress,
            ]);
            $user = $userModel->find($userId);
        } else {
            // Ensure fields are up to date
            $update = [
                'auth_source' => 'ldap',
                'ldap_dn'     => $entry['dn'] ?? $user['ldap_dn'],
            ];
            // Always refresh display_name/email from LDAP if provided
            if ($ldapDisplay) { $update['display_name'] = $ldapDisplay; }
            if ($ldapMail)    { $update['email'] = $ldapMail; }
            // For other profile fields: only fill in if currently empty to preserve local overrides
            if ($ldapFirst && empty($user['first_name'])) { $update['first_name'] = $ldapFirst; }
            if ($ldapLast && empty($user['last_name']))   { $update['last_name']  = $ldapLast; }
            if ($ldapPhone && empty($user['phone']))      { $update['phone']      = $ldapPhone; }
            if ($ldapAddress && empty($user['address']))  { $update['address']    = $ldapAddress; }

            $userModel->update($user['id'], $update);
            $user = $userModel->find($user['id']);
        }

        // Optional avatar from LDAP jpegPhoto: store only if user has none
        try {
            if (empty($user['profile_image']) && !empty($entry['jpegPhoto']) && is_string($entry['jpegPhoto'])) {
                $imgData = $entry['jpegPhoto'];
                // Create target directory
                $publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
                $targetDir = $publicDir . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . (int)$user['id'];
                if (! is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                // Write jpeg bytes (best guess, LDAP usually stores JPEG)
                $filePath = $targetDir . DIRECTORY_SEPARATOR . 'ldap.jpg';
                @file_put_contents($filePath, $imgData);
                if (is_file($filePath) && filesize($filePath) > 0) {
                    $rel = 'uploads/avatars/' . (int)$user['id'] . '/ldap.jpg';
                    $userModel->update((int)$user['id'], ['profile_image' => $rel]);
                    $user['profile_image'] = $rel;
                }
            }
        } catch (\Throwable $e) {
            // ignore avatar errors
        }

        if ((int) $user['is_active'] !== 1) {
            return redirect()->to('/login')->with('error', 'Account is disabled');
        }

        $this->setSessionForUser($user);
        return redirect()->to('/');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }

    private function setSessionForUser(array $user): void
    {
        session()->set([
            'user_id'    => $user['id'],
            'username'   => $user['username'],
            'display_name' => $user['display_name'] ?? $user['username'],
            'role'       => $user['role'] ?? 'user',
            'auth_source'=> $user['auth_source'],
            'is_active'  => (int) $user['is_active'],
            'logged_in'  => true,
        ]);

        // Apply per-user session duration if configured
        try {
            $settings = (new UserSettingModel())->getOrCreate((int) $user['id']);
            $ttl = (int) ($settings['session_duration'] ?? 7200);
            // Allow 0 (expire on browser close) and any positive integer
            if ($ttl >= 0) {
                // Store TTL in session for later use
                session()->set('session_duration', $ttl);
                // Refresh cookie with user-specific expiration
                $conf = config('Session');
                $cookieName = $conf->cookieName ?? 'ci_session';
                $sid = session_id();
                if ($sid) {
                    service('response')->setCookie($cookieName, $sid, $ttl, '', '', service('request')->isSecure(), true);
                }
            }
        } catch (\Throwable $e) {
            // ignore TTL customization errors
        }
    }
}
