<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Public routes
$routes->get('/', 'Home::index');
$routes->get('summit-admin', 'AuthController::login');
$routes->post('summit-admin', 'AuthController::authenticate');
$routes->post('logout', 'AuthController::logout');
$routes->get('p/(:any)/(:any)/(:any)', 'PublicController::asset/$1/$2/$3');
$routes->get('p/(:any)/(:any)', 'PublicController::asset/$1/$2');
$routes->get('p/(:any)', 'PublicController::show/$1');
$routes->post('p/(:any)/lead', 'PublicController::storeLead/$1');

// Admin routes (auth filter applied in Filters.php)
$routes->get('dashboard', 'DashboardController::index');
$routes->get('landing-pages', 'LandingPagesController::index');
$routes->get('landing-pages/create', 'LandingPagesController::create');
$routes->post('landing-pages', 'LandingPagesController::store');
$routes->get('landing-pages/edit/(:num)', 'LandingPagesController::edit/$1');
$routes->post('landing-pages/update/(:num)', 'LandingPagesController::update/$1');
$routes->get('landing-pages/delete/(:num)', 'LandingPagesController::delete/$1');
$routes->post('landing-pages/deactivate/(:num)', 'LandingPagesController::deactivate/$1');
$routes->post('landing-pages/activate/(:num)', 'LandingPagesController::activate/$1');

$routes->get('leads', 'LeadsController::index');
$routes->post('leads/archive/(:num)', 'LeadsController::archive/$1');

// API routes
$routes->group('api', ['filter' => 'bearerToken'], static function ($routes) {
    $routes->get('lp/list', 'Api\LpController::list');
    $routes->get('lp/leads', 'Api\LpController::allLeads');
    $routes->get('lp/leads/(:num)', 'Api\LpController::leads/$1');
});
