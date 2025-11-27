<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTilePingEnabled extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tiles')) {
            return;
        }
        if (! $this->db->fieldExists('ping_enabled', 'tiles')) {
            $this->forge->addColumn('tiles', [
                'ping_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => true, // NULL = inherit from user setting
                    'after'      => 'position',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('tiles')) {
            return;
        }
        if ($this->db->fieldExists('ping_enabled', 'tiles')) {
            $this->forge->dropColumn('tiles', 'ping_enabled');
        }
    }
}
