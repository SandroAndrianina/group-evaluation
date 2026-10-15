<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$admin = ['filter' => 'attendanceAdmin'];

// Admin (protégé)
$routes->group('attendance/admin', ['namespace' => 'Modules\Attendance\Controllers'], static function ($routes) use ($admin): void {
    $routes->get('login', 'AdminController::loginForm');
    $routes->post('login', 'AdminController::login');
    $routes->get('logout', 'AdminController::logout');

    $routes->get('/', 'AdminController::dashboard', $admin);
    $routes->post('sessions/open', 'AdminController::open', $admin);
    $routes->post('sessions/(:num)/close', 'AdminController::close/$1', $admin);
    $routes->get('sessions/(:num)/present', 'AdminController::present/$1', $admin);

    $routes->get('chef', 'AdminController::chef', $admin);
    $routes->get('qr/token', 'QrController::token', $admin);
});

// Public (étudiants)
$routes->group('attendance', ['namespace' => 'Modules\Attendance\Controllers'], static function ($routes): void {
    $routes->get('qr/verify', 'QrController::verify');
});