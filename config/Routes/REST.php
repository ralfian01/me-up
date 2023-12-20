<?php

use MVCME\Router\Static\REST;
use App\REST\V1 as RESTV1;

/*
 * ---------------------------------------------------
 * NEW AGE
 * ---------------------------------------------------
 */

REST::get('/', [RESTV1\Home::class, 'index'], ['middleware' => 'auth/basic']);
