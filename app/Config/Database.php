<?php

namespace Config;

use CodeIgniter\Database\Config as DatabaseConfig;

class Database extends DatabaseConfig
{
    public $default = [
        'DSN'      => '',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'toolpages',
        'DBDriver' => 'MySQLi',
        'port'     => 3306,
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => (ENVIRONMENT !== 'production'),
        'charset'  => 'utf8mb4',
        'DBCollat' => 'utf8mb4_unicode_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'portability' => 0,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->default['hostname'] = getenv('database.default.hostname') ?: $this->default['hostname'];
        $this->default['username'] = getenv('database.default.username') ?: $this->default['username'];
        $this->default['password'] = getenv('database.default.password') ?: $this->default['password'];
        $this->default['database'] = getenv('database.default.database') ?: $this->default['database'];
        $this->default['DBDriver'] = getenv('database.default.DBDriver') ?: $this->default['DBDriver'];
        $port = getenv('database.default.port');
        if ($port !== false) {
            $this->default['port'] = (int) $port;
        }
    }
}
