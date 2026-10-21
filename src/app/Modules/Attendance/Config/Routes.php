<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$admin = ['filter' => 'attendanceAdmin'];

$routes->group('attendance/admin', ['namespace' => 'Modules\Attendance\Controllers'], static function ($routes) use ($admin): void {
    $routes->get('login', 'AdminController::loginForm');
    $routes->post('login', 'AdminController::login');
    $routes->post('logout', 'AdminController::logout', $admin);

    $routes->get('/', 'AdminController::dashboard', $admin);
    $routes->post('sessions/open', 'AdminController::open', $admin);
    $routes->post('sessions/(:num)/close', 'AdminController::close/$1', $admin);
    $routes->get('sessions/(:num)/present', 'AdminController::present/$1', $admin);
    $routes->get('sessions/(:num)/export', 'AdminController::export/$1', $admin);
    $routes->post('sessions/(:num)/spot-check', 'AdminController::spotCheck/$1', $admin);

    $routes->get('chef', 'AdminController::chef', $admin);
    $routes->get('history', 'AdminController::history', $admin);
    $routes->get('qr/token', 'QrController::token', $admin);
});

$routes->group('attendance', ['namespace' => 'Modules\Attendance\Controllers'], static function ($routes): void {
    $routes->get('qr/verify', 'QrController::verify');
    $routes->get('scan', 'QrController::verify');
    $routes->post('passkey/register/options', 'QrController::registerOptions');
    $routes->post('passkey/register/finish', 'QrController::registerFinish');
    $routes->post('passkey/login/options', 'QrController::loginOptions');
    $routes->post('passkey/login/finish', 'QrController::loginFinish');
});
