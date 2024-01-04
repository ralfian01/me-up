<?php

use MVCME\Router\Static\REST;
use App\REST as RESTPage;
use App\REST\V1 as RESTV1;

/*
 * ---------------------------------------------------
 * NEW AGE
 * ---------------------------------------------------
 */

// Override 404 page
REST::setDefault404([RESTPage\Errors\Error404::class, 'index']);

REST::get('/', [RESTV1\Home::class, 'index']);
