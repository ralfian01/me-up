<?php

namespace App\Errors;

use MVCME\Controller;

class Error404 extends Controller
{
    public function show404()
    {
        require __DIR__ . '/View/404.php';
    }
}
