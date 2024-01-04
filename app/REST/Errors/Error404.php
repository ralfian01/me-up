<?php

namespace App\REST\Errors;

use MVCME\REST\BaseREST;

class Error404 extends BaseREST
{
    protected $directCall = false;

    public function index()
    {
        return $this->error(404);
    }
}
