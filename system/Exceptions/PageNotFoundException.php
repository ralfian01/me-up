<?php

namespace MVCME\Exceptions;

use Exception;

class PageNotFoundException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, 404);
    }
}
