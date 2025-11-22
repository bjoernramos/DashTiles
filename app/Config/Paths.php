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
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * ---------------------------------------------------------------
     * APP DIRECTORY
     * ---------------------------------------------------------------
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * ---------------------------------------------------------------
     * WRITABLE DIRECTORY
     * ---------------------------------------------------------------
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * ---------------------------------------------------------------
     * TESTS DIRECTORY
     * ---------------------------------------------------------------
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * ---------------------------------------------------------------
     * ROOT DIRECTORY
     * ---------------------------------------------------------------
     */
    public string $rootDirectory = __DIR__ . '/../../';
}
