<?php

namespace App\Middleware;

use MVCME\Middleware\MiddlewareInterface;
use MVCME\Request\HTTPRequestInterface;
use MVCME\Response\HTTPResponseInterface;

class APIKey implements MiddlewareInterface
{
    public function before(HTTPRequestInterface $request, $arguments = null)
    {
        // Code...
    }

    public function after(HTTPRequestInterface $request, HTTPResponseInterface $response, $arguments = null)
    {
        // Code...
    }
}
