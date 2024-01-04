<?php

namespace App\REST\V1;

interface BaseRESTV1Interface
{
    /**
     * The main method called by routes
     * @return object|string
     */
    public function index();
}
