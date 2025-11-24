<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGlobalFlagToTiles extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tiles')) {
            $fields = [
                'is_global' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'user_id',
                ],
            ];
            $this->forge->addColumn('tiles', $fields);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tiles')) {
            $this->forge->dropColumn('tiles', 'is_global');
        }
    }
}
