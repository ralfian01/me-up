<?php

namespace AppConfig;

class REST extends App
{
    /**
     * @var array Hostname that is allowed to access the application
     */
    public $allowedHostnames = [
        '.',
        'api',
        'accounts',
        'admin',
        'member'
    ];

    /**
     * @var array Allowed Headers
     * 
     * This determines which headers the requester may include
     */
    public $allowedHeaders = [
        'Authorization',
        'Content-Type',
        'X-Requested-With'
    ];

    /**
     * @var array Allowed HTTP Method
     * 
     * This determines which http methods can be included by the requester
     */
    public $allowedHTTPMethod = [
        'GET',
        'POST',
        'PATCH',
        'PUT',
        'DELETE',
        'OPTIONS'
    ];
}
