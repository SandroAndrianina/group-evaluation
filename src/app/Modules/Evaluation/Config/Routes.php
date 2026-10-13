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
    $routes->get('groupes', 'GroupController::index');
    $routes->get('groupes/nouveau', 'GroupController::create');
    $routes->post('groupes', 'GroupController::store');
    $routes->get('groupes/(:num)', 'GroupController::edit/$1');
    $routes->post('groupes/(:num)', 'GroupController::update/$1');
    $routes->get('groupes/(:num)/supprimer', 'GroupController::delete/$1');
    $routes->get('groupes/(:num)/membres', 'GroupController::members/$1');
    $routes->post('groupes/(:num)/membres', 'GroupController::addMember/$1');
    $routes->get('groupes/(:num)/membres/(:num)/supprimer', 'GroupController::removeMember/$1/$2');
    $routes->get('liste', 'EvaluationController::adminList');
    $routes->get('liste/export', 'EvaluationController::exportCsv');
    $routes->get('liste/(:num)', 'EvaluationController::edit/$1');
    $routes->post('liste/(:num)', 'EvaluationController::editStore/$1');
    $routes->get('liste/(:num)/supprimer', 'EvaluationController::destroy/$1');
});