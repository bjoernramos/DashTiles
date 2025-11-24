<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTilesTables extends Migration
{
    public function up()
    {
        // tiles table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['link','iframe','file'],
                'default'    => 'link',
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => false,
            ],
            'url' => [ // for link or iframe, or relative file path for file type
                'type'       => 'VARCHAR',
                'constraint' => 1024,
                'null'       => true,
            ],
            'icon' => [ // optional icon name or URL
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'text' => [ // optional text label
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'category' => [ // grouping into rows
                'type'       => 'VARCHAR',
                'constraint' => 190,
                'null'       => true,
            ],
            'position' => [ // ordering within category
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'category']);
        $this->forge->createTable('tiles');

        // user_settings table
        $this->forge->addField([
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'columns' => [ // number of columns for dashboard grid
                'type'    => 'INT',
                'constraint' => 2,
                'default' => 3,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('user_id', true);
        $this->forge->createTable('user_settings');
    }

    public function down()
    {
        $this->forge->dropTable('user_settings', true);
        $this->forge->dropTable('tiles', true);
    }
}
