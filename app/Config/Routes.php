<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('health', 'Home::health');

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
});
