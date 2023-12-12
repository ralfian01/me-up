<?php

use App\Controllers\Home;
use MVCME\Router\RoutePack;

/**
 * @var RoutePack $routes
 */
$routes->group('test1', function ($routes) {
    $routes->get('/', function () {
        return "Hello world";
    });

    $routes->get('(:segment)', [Home::class, 'index']);
});
// $routes->get('test1', function () {
//     return "Hello world 1";
// });
// $routes->get('test1/(:segment)', function () {
//     return "Hello world";
// });
// $routes->get('test1/(:segment)', [Home::class, 'index']);

$routes->get('test2', [Home::class, 'index']);
