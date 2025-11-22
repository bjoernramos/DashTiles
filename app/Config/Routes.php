<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

// Determine base path (subfolder) for routing convenience
$basePath = getenv('toolpages.basePath') ?: '/toolpages';
$basePath = rtrim($basePath, '/');
if ($basePath === '') {
    $basePath = '/toolpages';
}

// Default settings
$routes->setDefaultNamespace('App\\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();

// Home route under base path
$routes->get($basePath, 'Home::index');
$routes->get($basePath . '/', 'Home::index');

// Health route
$routes->get($basePath . '/health', 'Home::health');
