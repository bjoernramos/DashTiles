<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsersProfileFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('users')) {
            return;
        }
        $fields = [];
        if (! $this->db->fieldExists('first_name', 'users')) {
            $fields['first_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => true,
                'after'      => 'display_name',
            ];
        }
        if (! $this->db->fieldExists('last_name', 'users')) {
            $fields['last_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => true,
                'after'      => 'first_name',
            ];
        }
        if (! $this->db->fieldExists('phone', 'users')) {
            $fields['phone'] = [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => true,
                'after'      => 'email',
            ];
        }
        if (! $this->db->fieldExists('address', 'users')) {
            $fields['address'] = [
                'type'       => 'VARCHAR',
                'constraint' => 512,
                'null'       => true,
                'after'      => 'phone',
            ];
        }
        if (! $this->db->fieldExists('profile_image', 'users')) {
            $fields['profile_image'] = [
                'type'       => 'VARCHAR',
                'constraint' => 512,
                'null'       => true,
                'after'      => 'address',
            ];
        }
        if (! empty($fields)) {
            $this->forge->addColumn('users', $fields);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('users')) {
            return;
        }
        foreach (['first_name','last_name','phone','address','profile_image'] as $col) {
            if ($this->db->fieldExists($col, 'users')) {
                $this->forge->dropColumn('users', $col);
            }
        }
    }
}
