<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPingEnabledToUserSettings extends Migration
{
    public function up()
    {
        // Only proceed if user_settings table exists
        if (! $this->db->tableExists('user_settings')) {
            return;
        }
        if (! $this->db->fieldExists('ping_enabled', 'user_settings')) {
            $this->forge->addColumn('user_settings', [
                'ping_enabled' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'null'       => false,
                    'after'      => 'columns',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('user_settings')) {
            return;
        }
        if ($this->db->fieldExists('ping_enabled', 'user_settings')) {
            $this->forge->dropColumn('user_settings', 'ping_enabled');
        }
    }
}
