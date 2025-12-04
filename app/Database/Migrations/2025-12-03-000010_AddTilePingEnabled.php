<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTilePingEnabled extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tiles')) {
            // Add ping_enabled column with default 1 if it doesn't exist yet
            $fields = $this->db->getFieldData('tiles');
            $hasColumn = false;
            foreach ($fields as $f) {
                if (isset($f->name) && $f->name === 'ping_enabled') { $hasColumn = true; break; }
            }
            if (! $hasColumn) {
                $this->forge->addColumn('tiles', [
                    'ping_enabled' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'null'       => false,
                        'default'    => 1,
                        'after'      => 'position',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tiles')) {
            // Be conservative: only drop if present
            $fields = $this->db->getFieldData('tiles');
            $hasColumn = false;
            foreach ($fields as $f) {
                if (isset($f->name) && $f->name === 'ping_enabled') { $hasColumn = true; break; }
            }
            if ($hasColumn) {
                $this->forge->dropColumn('tiles', 'ping_enabled');
            }
        }
    }
}
