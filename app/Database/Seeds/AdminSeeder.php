<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'username'      => 'admin',
            'display_name'  => 'Administrator',
            'email'         => null,
            'auth_source'   => 'local',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'ldap_dn'       => null,
            'role'          => 'admin',
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        // Upsert: insert if username not exists
        $db = \Config\Database::connect();
        $builder = $db->table('users');
        $existing = $builder->where('username', $data['username'])->get()->getRowArray();
        if ($existing) {
            $builder->where('id', $existing['id'])->update($data);
        } else {
            $builder->insert($data);
        }
    }
}
