<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGroupsAndMemberships extends Migration
{
    public function up()
    {
        // groups table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => false,
            ],
            'created_at' => [ 'type' => 'DATETIME', 'null' => true ],
            'updated_at' => [ 'type' => 'DATETIME', 'null' => true ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('groups');

        // user_groups pivot
        $this->forge->addField([
            'user_id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
            'group_id'=> [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
        ]);
        $this->forge->addKey(['user_id', 'group_id'], true);
        $this->forge->createTable('user_groups');
    }

    public function down()
    {
        $this->forge->dropTable('user_groups', true);
        $this->forge->dropTable('groups', true);
    }
}
