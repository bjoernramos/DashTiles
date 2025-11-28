<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserSettingModel;

class Profile extends BaseController
{
    public function index()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return redirect()->to('/login');
        }
        $user = (new UserModel())->find($userId);
        $settings = (new UserSettingModel())->getOrCreate($userId);

        return view('profile/index', [
            'user' => $user,
            'settings' => $settings,
        ]);
    }

    public function updateProfile()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) { return redirect()->to('/login'); }

        $data = [
            'display_name' => trim((string) $this->request->getPost('display_name')) ?: null,
            'first_name'   => trim((string) $this->request->getPost('first_name')) ?: null,
            'last_name'    => trim((string) $this->request->getPost('last_name')) ?: null,
            'email'        => trim((string) $this->request->getPost('email')) ?: null,
            'phone'        => trim((string) $this->request->getPost('phone')) ?: null,
            'address'      => trim((string) $this->request->getPost('address')) ?: null,
        ];
        $userModel = new UserModel();
        if (! $userModel->update($userId, $data)) {
            return redirect()->to('/profile')->with('error', implode("\n", $userModel->errors() ?: ['Ungültige Eingaben.']));
        }
        // refresh session display name
        $fresh = $userModel->find($userId);
        session()->set('display_name', $fresh['display_name'] ?? $fresh['username'] ?? '');
        return redirect()->to('/profile')->with('success', 'Profil gespeichert.');
    }

    public function updatePassword()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) { return redirect()->to('/login'); }
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if (($user['auth_source'] ?? 'local') !== 'local') {
            return redirect()->to('/profile')->with('error', 'Passwortänderung ist bei LDAP-Nutzern deaktiviert.');
        }
        $current = (string) ($this->request->getPost('current_password') ?? '');
        $new     = (string) ($this->request->getPost('new_password') ?? '');
        $confirm = (string) ($this->request->getPost('confirm_password') ?? '');
        if ($new === '' || $new !== $confirm) {
            return redirect()->to('/profile')->with('error', 'Neues Passwort und Bestätigung stimmen nicht überein.');
        }
        if (!empty($user['password_hash']) && ! password_verify($current, $user['password_hash'])) {
            return redirect()->to('/profile')->with('error', 'Aktuelles Passwort ist falsch.');
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $userModel->update($userId, ['password_hash' => $hash]);
        return redirect()->to('/profile')->with('success', 'Passwort aktualisiert.');
    }

    public function updateAvatar()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) { return redirect()->to('/login'); }
        $file = $this->request->getFile('avatar');
        if (! $file || ! $file->isValid()) {
            return redirect()->to('/profile')->with('error', 'Kein gültiges Bild hochgeladen.');
        }
        $mime = strtolower((string) $file->getMimeType());
        $allowed = ['image/png','image/jpeg','image/jpg','image/webp','image/gif'];
        if (! in_array($mime, $allowed, true)) {
            return redirect()->to('/profile')->with('error', 'Nur PNG, JPG, WEBP oder GIF erlaubt.');
        }
        $publicDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
        $targetDir = $publicDir . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $userId;
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }
        // remove previous avatar files
        foreach (glob($targetDir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) { @unlink($path); }
        $newName = $file->getRandomName();
        $file->move($targetDir, $newName);
        $rel = 'uploads/avatars/' . $userId . '/' . $newName;
        (new UserModel())->update($userId, ['profile_image' => $rel]);
        return redirect()->to('/profile')->with('success', 'Profilbild aktualisiert.');
    }

    public function updateSettings()
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) { return redirect()->to('/login'); }
        $ping = $this->request->getPost('ping_enabled') ? 1 : 0;
        $bg   = $this->request->getPost('background_enabled') ? 1 : 0;
        $settings = new UserSettingModel();
        $exists = $settings->find($userId);
        $payload = ['user_id' => $userId, 'ping_enabled' => $ping, 'background_enabled' => $bg];
        if ($exists) { $settings->update($userId, $payload); }
        else { $settings->insert($payload + ['columns' => 3]); }
        return redirect()->to('/profile')->with('success', 'Einstellungen gespeichert.');
    }
}
