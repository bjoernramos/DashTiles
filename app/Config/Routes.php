<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('health', 'Home::health');
// Same-origin ping endpoint to check external URLs without CORS/CORP noise
$routes->get('ping', 'Home::ping');
// Locale switch
$routes->get('lang/(:segment)', 'Locale::set/$1');

// Auth
$routes->get('login', 'AuthController::showLogin');
$routes->post('login/local', 'AuthController::postLocal');
$routes->post('login/ldap', 'AuthController::postLdap');
$routes->get('logout', 'AuthController::logout');

// Admin routes
$routes->group('admin', ['filter' => 'admin'], static function (RouteCollection $routes) {
    $routes->get('users', 'Admin\\Users::index');
    $routes->get('users/create', 'Admin\\Users::create');
    $routes->post('users/store', 'Admin\\Users::store');
    $routes->post('users/(\d+)/toggle', 'Admin\\Users::toggle/$1');
    $routes->post('users/(\d+)/role', 'Admin\\Users::changeRole/$1');
    $routes->post('users/(\d+)/delete', 'Admin\\Users::delete/$1');

    // Groups management
    $routes->get('groups', 'Admin\\Groups::index');
    $routes->get('groups/create', 'Admin\\Groups::create');
    $routes->post('groups/store', 'Admin\\Groups::store');
    $routes->get('groups/(\d+)/members', 'Admin\\Groups::editMembers/$1');
    $routes->post('groups/(\d+)/members', 'Admin\\Groups::updateMembers/$1');
    $routes->post('groups/(\d+)/delete', 'Admin\\Groups::delete/$1');
});

// Dashboard routes (authenticated users)
$routes->get('dashboard', 'Dashboard::index');
$routes->post('dashboard/settings', 'Dashboard::saveSettings');
$routes->post('dashboard/tile', 'Dashboard::store');
$routes->post('dashboard/tile/(\d+)', 'Dashboard::update/$1');
$routes->post('dashboard/tile/(\d+)/delete', 'Dashboard::delete/$1');
// Personalize tiles: reorder and hide/unhide
$routes->post('dashboard/reorder', 'Dashboard::reorder');
$routes->post('dashboard/tile/(\d+)/hide', 'Dashboard::hide/$1');
$routes->post('dashboard/tile/(\d+)/unhide', 'Dashboard::unhide/$1');
// Serve user file tiles securely
$routes->get('file/(\d+)', 'Dashboard::file/$1');

// Plugins: static serving and listing
$routes->get('plugins/(:segment)/plugin.json', 'PluginsController::manifest/$1');
$routes->get('plugins/(:segment)/web/(.+)', 'PluginsController::web/$1/$2');
$routes->get('api/plugins', 'PluginsController::index');
// Plugin-scoped proxy endpoints (Phase 5 - minimal for rss_reader)
$routes->post('api/plugins/rss_reader/fetch', 'PluginsController::proxyRss');
