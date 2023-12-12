<?php

use App\Controllers\Home;
use MVCME\Router\RoutePackInterface;

/**
 * @var RoutePackInterface $routes
 */
$routes->get('/', [Home::class, 'index']);
