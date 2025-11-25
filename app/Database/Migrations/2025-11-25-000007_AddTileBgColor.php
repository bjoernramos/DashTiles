<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTileBgColor extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tiles')) {
            if (! $this->db->fieldExists('bg_color', 'tiles')) {
                $this->forge->addColumn('tiles', [
                    'bg_color' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 1024, // allow gradients or CSS color strings
                        'null'       => true,
                        'after'      => 'bg_path',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tiles') && $this->db->fieldExists('bg_color', 'tiles')) {
            $this->forge->dropColumn('tiles', 'bg_color');
        }
    }
}
