<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\LDAPService;

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
        } catch (\Throwable $e) {
            return redirect()->to('/login')->with('error', 'LDAP error: ' . $e->getMessage());
        }

        $userModel = new UserModel();
        $user = $userModel->findByUsername($username);
        if (! $user) {
            // Auto-create
            $userId = $userModel->insert([
                'username'      => $entry['uid'] ?? $username,
                'display_name'  => $entry['cn'] ?? null,
                'email'         => $entry['mail'] ?? null,
                'auth_source'   => 'ldap',
                'password_hash' => null,
                'ldap_dn'       => $entry['dn'] ?? null,
                'role'          => 'user',
                'is_active'     => 1,
            ]);
            $user = $userModel->find($userId);
        } else {
            // Ensure fields are up to date
            $userModel->update($user['id'], [
                'auth_source' => 'ldap',
                'ldap_dn'     => $entry['dn'] ?? $user['ldap_dn'],
                'display_name'=> $entry['cn'] ?? $user['display_name'],
                'email'       => $entry['mail'] ?? $user['email'],
            ]);
            $user = $userModel->find($user['id']);
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
    }
}
