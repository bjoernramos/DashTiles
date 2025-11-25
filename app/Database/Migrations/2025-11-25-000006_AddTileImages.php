<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTileImages extends Migration
{
    public function up()
    {
        // Add optional columns for uploaded icon and background images
        if ($this->db->tableExists('tiles')) {
            // icon_path
            if (! $this->db->fieldExists('icon_path', 'tiles')) {
                $this->forge->addColumn('tiles', [
                    'icon_path' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 512,
                        'null'       => true,
                        'after'      => 'icon',
                    ],
                ]);
            }
            // bg_path
            if (! $this->db->fieldExists('bg_path', 'tiles')) {
                $this->forge->addColumn('tiles', [
                    'bg_path' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 512,
                        'null'       => true,
                        'after'      => 'icon_path',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tiles')) {
            if ($this->db->fieldExists('icon_path', 'tiles')) {
                $this->forge->dropColumn('tiles', 'icon_path');
            }
            if ($this->db->fieldExists('bg_path', 'tiles')) {
                $this->forge->dropColumn('tiles', 'bg_path');
            }
        }
    }
}
