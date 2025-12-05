<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSessionDurationToUserSettings extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('user_settings')) {
            return; // created by initial migration
        }
        if (! $this->db->fieldExists('session_duration', 'user_settings')) {
            $this->forge->addColumn('user_settings', [
                'session_duration' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 7200,
                    'null' => false,
                    'after' => 'search_autofocus',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('user_settings')) { return; }
        if ($this->db->fieldExists('session_duration', 'user_settings')) {
            $this->forge->dropColumn('user_settings', 'session_duration');
        }
    }
}
