<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserTilePrefs extends Migration
{
    public function up()
    {
        // user_tile_prefs: per-user Einstellungen zu Kacheln (versteckt, Reihenfolge)
        $this->forge->addField([
            'user_id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
            'tile_id' => [ 'type' => 'INT', 'constraint' => 11, 'unsigned' => true ],
            'hidden'  => [ 'type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false ],
            'position'=> [ 'type' => 'INT', 'constraint' => 11, 'null' => true ],
            'updated_at' => [ 'type' => 'DATETIME', 'null' => true ],
        ]);
        $this->forge->addKey(['user_id', 'tile_id'], true);
        $this->forge->createTable('user_tile_prefs');
    }

    public function down()
    {
        $this->forge->dropTable('user_tile_prefs', true);
    }
}
