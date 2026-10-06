<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('evaluations', ['namespace' => 'Modules\Evaluation\UI\Controllers'], static function ($routes): void {
    $routes->get('ping', 'PingController::index');
    $routes->get('new', 'EvaluationController::create');
    $routes->post('', 'EvaluationController::store');
    $routes->get('confirmation', 'EvaluationController::confirmation');
    $routes->get('classement', 'EvaluationController::ranking');
    $routes->get('notes-enseignant', 'EvaluationController::teacherList');
    $routes->get('notes-enseignant/(:num)', 'EvaluationController::teacherForm/$1');
    $routes->post('notes-enseignant/(:num)', 'EvaluationController::teacherStore/$1');
});