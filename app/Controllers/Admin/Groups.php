<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GroupModel;
use App\Models\UserGroupModel;
use App\Models\UserModel;
use App\Models\TileGroupModel;

class Groups extends BaseController
{
    public function index()
    {
        $groups = (new GroupModel())->orderBy('name','asc')->findAll();

        // Build members list per group for display and modal defaults
        $ug = new UserGroupModel();
        $userModel = new UserModel();
        $memberships = $ug->findAll();
        $groupUserIds = [];
        foreach ($memberships as $m) {
            $gid = (int)$m['group_id'];
            $uid = (int)$m['user_id'];
            $groupUserIds[$gid] = $groupUserIds[$gid] ?? [];
            $groupUserIds[$gid][] = $uid;
        }
        $allUsers = $userModel->select('id, display_name, username')->where('is_active', 1)->orderBy('display_name','asc')->findAll();

        return view('admin/groups/index', [
            'groups' => $groups,
            'groupUserIds' => $groupUserIds,
            'users' => $allUsers,
        ]);
    }

    public function create()
    {
        return view('admin/groups/create');
    }

    public function store()
    {
        $model = new GroupModel();
        $name = trim((string) $this->request->getPost('name'));
        if (! $model->insert(['name' => $name])) {
            return redirect()->back()->withInput()->with('error', implode("\n", $model->errors() ?: ['Speichern fehlgeschlagen']));
        }
        return redirect()->to('/admin/groups')->with('success', 'Gruppe angelegt');
    }

    public function editMembers(int $groupId)
    {
        $g = (new GroupModel())->find($groupId);
        if (! $g) {
            return redirect()->to('/admin/groups')->with('error', 'Gruppe nicht gefunden');
        }
        $users = (new UserModel())->orderBy('display_name','asc')->findAll();
        $current = (new UserGroupModel())->where('group_id', $groupId)->findAll();
        $currentUserIds = array_map(static function($r){ return (int)$r['user_id']; }, $current);
        return view('admin/groups/members', [
            'group' => $g,
            'users' => $users,
            'currentUserIds' => $currentUserIds,
        ]);
    }

    public function updateMembers(int $groupId)
    {
        $g = (new GroupModel())->find($groupId);
        if (! $g) {
            return redirect()->to('/admin/groups')->with('error', 'Gruppe nicht gefunden');
        }
        $selected = $this->request->getPost('user_ids');
        $selected = is_array($selected) ? array_values(array_unique(array_map('intval', $selected))) : [];

        $pivot = new UserGroupModel();
        // clear existing
        $pivot->where('group_id', $groupId)->delete();
        // insert new
        foreach ($selected as $uid) {
            if ($uid > 0) {
                $pivot->insert(['group_id' => $groupId, 'user_id' => $uid]);
            }
        }
        return redirect()->to('/admin/groups')->with('success', 'Mitglieder aktualisiert');
    }

    public function delete(int $groupId)
    {
        $model = new GroupModel();
        $group = $model->find($groupId);
        if (! $group) {
            return redirect()->to('/admin/groups')->with('error', 'Gruppe nicht gefunden');
        }

        $db = \Config\Database::connect();
        $db->transStart();
        // Pivots entfernen
        (new UserGroupModel())->where('group_id', $groupId)->delete();
        (new TileGroupModel())->where('group_id', $groupId)->delete();
        // Gruppe löschen
        $model->delete($groupId);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to('/admin/groups')->with('error', 'Löschen fehlgeschlagen');
        }
        return redirect()->to('/admin/groups')->with('success', 'Gruppe gelöscht');
    }
}
