<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBackgroundEnabledToUserSettings extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('user_settings')) {
            return;
        }
        if (! $this->db->fieldExists('background_enabled', 'user_settings')) {
            $this->forge->addColumn('user_settings', [
                'background_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'ping_enabled',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('user_settings')) {
            return;
        }
        if ($this->db->fieldExists('background_enabled', 'user_settings')) {
            $this->forge->dropColumn('user_settings', 'background_enabled');
        }
    }
}
