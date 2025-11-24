<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTileVisibilityPivots extends Migration
{
    public function up()
    {
        // tile_users: explizite Benutzersichtbarkeit
        $this->forge->addField([
            'tile_id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
            'user_id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
        ]);
        $this->forge->addKey(['tile_id', 'user_id'], true);
        $this->forge->createTable('tile_users');

        // tile_groups: Gruppensichtbarkeit
        $this->forge->addField([
            'tile_id'  => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
            'group_id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
        ]);
        $this->forge->addKey(['tile_id', 'group_id'], true);
        $this->forge->createTable('tile_groups');
    }

    public function down()
    {
        $this->forge->dropTable('tile_groups', true);
        $this->forge->dropTable('tile_users', true);
    }
}
