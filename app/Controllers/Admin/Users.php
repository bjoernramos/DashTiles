<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\UserGroupModel;
use App\Models\TileModel;
use App\Models\TileUserModel;
use App\Models\TileGroupModel;
use App\Models\UserSettingModel;

class Users extends BaseController
{
    public function index()
    {
        $model = new UserModel();
        $users = $model->orderBy('id', 'ASC')->findAll();
        return view('admin/users/index', ['users' => $users]);
    }

    public function create()
    {
        return view('admin/users/create');
    }

    public function store()
    {
        $data = [
            'username'      => trim((string) $this->request->getPost('username')),
            'display_name'  => trim((string) $this->request->getPost('display_name')) ?: null,
            'email'         => trim((string) $this->request->getPost('email')) ?: null,
            'auth_source'   => 'local',
            'password_hash' => null,
            'ldap_dn'       => null,
            'role'          => in_array($this->request->getPost('role'), ['admin','user'], true) ? (string) $this->request->getPost('role') : 'user',
            'is_active'     => $this->request->getPost('is_active') ? 1 : 0,
        ];

        $password = (string) ($this->request->getPost('password') ?? '');
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $model = new UserModel();
        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('error', implode('\n', $model->errors() ?: ['Failed to create user']));
        }

        return redirect()->to('/admin/users')->with('success', 'User created');
    }

    public function toggle(int $id)
    {
        $model = new UserModel();
        $user = $model->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', 'User not found');
        }
        $model->update($id, ['is_active' => (int) (! (int) $user['is_active'])]);
        return redirect()->to('/admin/users')->with('success', 'User updated');
    }

    public function changeRole(int $id)
    {
        $role = (string) $this->request->getPost('role');
        if (! in_array($role, ['admin','user'], true)) {
            return redirect()->to('/admin/users')->with('error', 'Invalid role');
        }
        $model = new UserModel();
        if (! $model->find($id)) {
            return redirect()->to('/admin/users')->with('error', 'User not found');
        }
        $model->update($id, ['role' => $role]);
        return redirect()->to('/admin/users')->with('success', 'Role updated');
    }

    public function delete(int $id)
    {
        $currentId = (int) session()->get('user_id');
        if ($id === $currentId) {
            return redirect()->to('/admin/users')->with('error', 'Du kannst dich nicht selbst löschen.');
        }

        $users = new UserModel();
        $user = $users->find($id);
        if (! $user) {
            return redirect()->to('/admin/users')->with('error', 'User not found');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1) Entferne Gruppenmitgliedschaften des Users
        (new UserGroupModel())->where('user_id', $id)->delete();

        // 2) Entferne explizite Tile-Sichtbarkeiten für diesen User
        (new TileUserModel())->where('user_id', $id)->delete();

        // 3) Tiles des Users löschen und deren Pivots bereinigen
        $tileModel = new TileModel();
        $userTiles = $tileModel->select('id')->where('user_id', $id)->findAll();
        $tileIds = array_map(static fn($t) => (int)$t['id'], $userTiles);
        if (!empty($tileIds)) {
            // Pivots
            (new TileUserModel())->whereIn('tile_id', $tileIds)->delete();
            (new TileGroupModel())->whereIn('tile_id', $tileIds)->delete();
            // Tiles (soft delete)
            foreach ($tileIds as $tid) {
                $tileModel->delete($tid);
            }
        }

        // 4) User Settings entfernen
        (new UserSettingModel())->where('user_id', $id)->delete();

        // 5) User selbst löschen (soft delete)
        $users->delete($id);

        $db->transComplete();

        // 6) Best-effort: Upload-Verzeichnis entfernen
        try {
            $writable = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR;
            $dir = $writable . 'uploads' . DIRECTORY_SEPARATOR . $id;
            if (is_dir($dir)) {
                $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
                $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $file) {
                    if ($file->isDir()) {
                        @rmdir($file->getRealPath());
                    } else {
                        @unlink($file->getRealPath());
                    }
                }
                @rmdir($dir);
            }
        } catch (\Throwable $e) {
            // ignore filesystem cleanup errors
        }

        if ($db->transStatus() === false) {
            return redirect()->to('/admin/users')->with('error', 'Löschen fehlgeschlagen');
        }
        return redirect()->to('/admin/users')->with('success', 'User gelöscht');
    }
}
