<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSearchSettingsToUserSettings extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('user_settings')) {
            return; // initial migration will create the table later
        }
        // Add columns if they don't exist
        if (! $this->db->fieldExists('search_tile_enabled', 'user_settings')) {
            $this->forge->addColumn('user_settings', [
                'search_tile_enabled' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                    'after' => 'background_enabled',
                ],
            ]);
        }
        if (! $this->db->fieldExists('search_engine', 'user_settings')) {
            $this->forge->addColumn('user_settings', [
                'search_engine' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'google',
                    'null' => false,
                    'after' => 'search_tile_enabled',
                ],
            ]);
        }
        if (! $this->db->fieldExists('search_autofocus', 'user_settings')) {
            $this->forge->addColumn('user_settings', [
                'search_autofocus' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => false,
                    'after' => 'search_engine',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('user_settings')) { return; }
        if ($this->db->fieldExists('search_autofocus', 'user_settings')) {
            $this->forge->dropColumn('user_settings', 'search_autofocus');
        }
        if ($this->db->fieldExists('search_engine', 'user_settings')) {
            $this->forge->dropColumn('user_settings', 'search_engine');
        }
        if ($this->db->fieldExists('search_tile_enabled', 'user_settings')) {
            $this->forge->dropColumn('user_settings', 'search_tile_enabled');
        }
    }
}
