<?php

namespace Config;

use CodeIgniter\Filters\Filters as BaseFilters;

class Filters extends BaseFilters
{
    public $aliases = [
        'csrf'    => \CodeIgniter\Filters\CSRF::class,
        'toolbar' => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot'=> \CodeIgniter\Filters\Honeypot::class,
    ];

    public $globals = [
        'before' => [
            // 'csrf'
        ],
        'after'  => [
            'toolbar',
        ],
    ];

    public $methods = [];

    public $filters = [];
}
