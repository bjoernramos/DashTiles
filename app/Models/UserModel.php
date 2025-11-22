<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'username', 'display_name', 'email', 'auth_source', 'password_hash',
        'ldap_dn', 'role', 'is_active',
    ];

    protected $returnType = 'array';

    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[190]|is_unique[users.username,id,{id}]',
        'email'    => 'permit_empty|valid_email|max_length[190]',
        'role'     => 'required|in_list[admin,user]',
        'auth_source' => 'required|in_list[local,ldap]',
    ];

    protected $validationMessages = [];

    protected $skipValidation = false;

    public function findByUsername(string $username)
    {
        $row = $this->where('username', $username)->first();
        return $row ?: null;
    }
}
