<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

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
}
