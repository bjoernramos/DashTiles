<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = '';

    public string $indexPage = '';

    public string $defaultLocale = 'en';

    public bool $negotiateLocale = false;

    public string $appTimezone = 'UTC';

    public string $charset = 'UTF-8';

    public bool $CSPEnabled = false;

    public function __construct()
    {
        parent::__construct();

        $this->baseURL    = getenv('app.baseURL') ?: 'http://localhost:8080/toolpages/';
        $this->indexPage  = getenv('app.indexPage') !== false ? (string) getenv('app.indexPage') : '';
        $this->CSPEnabled = (bool) (getenv('app.CSPEnabled') ?: false);
    }
}
