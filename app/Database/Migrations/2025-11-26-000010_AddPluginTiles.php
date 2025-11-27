<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPluginTiles extends Migration
{
    public function up()
    {
        // Only proceed if tiles table exists
        if (! $this->db->tableExists('tiles')) {
            return;
        }

        // Add columns if they don't exist
        if (! $this->db->fieldExists('plugin_type', 'tiles')) {
            $this->forge->addColumn('tiles', [
                'plugin_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => true,
                    'after'      => 'type',
                ],
            ]);
        }
        if (! $this->db->fieldExists('plugin_config', 'tiles')) {
            $this->forge->addColumn('tiles', [
                'plugin_config' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'plugin_type',
                ],
            ]);
        }

        // Extend ENUM for type to include 'plugin' if using MySQL
        $driver = strtolower((string) $this->db->DBDriver);
        if ($driver === 'mysqli' || $driver === 'mysql') {
            try {
                $this->db->query("ALTER TABLE `tiles` MODIFY `type` ENUM('link','iframe','file','plugin') NOT NULL DEFAULT 'link'");
            } catch (\Throwable $e) {
                // Ignore if database does not support ENUM alteration or already altered
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('tiles')) {
            return;
        }

        // Revert ENUM change (optional)
        $driver = strtolower((string) $this->db->DBDriver);
        if ($driver === 'mysqli' || $driver === 'mysql') {
            try {
                $this->db->query("ALTER TABLE `tiles` MODIFY `type` ENUM('link','iframe','file') NOT NULL DEFAULT 'link'");
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Drop plugin columns if exist
        if ($this->db->fieldExists('plugin_config', 'tiles')) {
            $this->forge->dropColumn('tiles', 'plugin_config');
        }
        if ($this->db->fieldExists('plugin_type', 'tiles')) {
            $this->forge->dropColumn('tiles', 'plugin_type');
        }
    }
}
