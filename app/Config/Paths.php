<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Paths Configuration
 *
 * This file contains the paths that are used by the framework
 * to locate the main directories, app, system, writable, etc.
 */
class Paths extends BaseConfig
{
    /**
     * ---------------------------------------------------------------
     * SYSTEM DIRECTORY
     * ---------------------------------------------------------------
     *
     * The directory name, relative to the root directory, that holds
     * the CodeIgniter framework.
     */
    public $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * ---------------------------------------------------------------
     * APP DIRECTORY
     * ---------------------------------------------------------------
     */
    public $appDirectory = __DIR__ . '/..';

    /**
     * ---------------------------------------------------------------
     * WRITABLE DIRECTORY
     * ---------------------------------------------------------------
     */
    public $writableDirectory = __DIR__ . '/../../writable';

    /**
     * ---------------------------------------------------------------
     * TESTS DIRECTORY
     * ---------------------------------------------------------------
     */
    public $testsDirectory = __DIR__ . '/../../tests';

    /**
     * ---------------------------------------------------------------
     * ROOT DIRECTORY
     * ---------------------------------------------------------------
     */
    public $rootDirectory = __DIR__ . '/../../';
}
