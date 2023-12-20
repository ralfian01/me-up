<?php

use App\Web\Home;
use MVCME\Router\Static\Web;

/*
 * ---------------------------------------------------
 * NEW AGE
 * ---------------------------------------------------
 */

Web::get('/', [Home::class, 'index']);
