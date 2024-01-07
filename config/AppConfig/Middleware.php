<?php

namespace AppConfig;

use MVCME\Config\Middleware as ConfigMiddleware;
use App\Middleware\APIKey;

class Middleware extends ConfigMiddleware
{

    /**
     * Configures aliases for middleware classes to make reading things nicer and simpler.
     *
     * @var array
     * 
     * How to use:
     * - [middleware_name => classname]
     * - [middleware_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'Auth.APIKey' => APIKey::class,
    ];

    /**
     * List of middleware aliases that are always applied before and after every request
     */
    public array $globals = [
        'before' => [
            // 'apikey'
            // 'csrf',
        ],
        'after' => [
            // 'secureheaders',
        ],
    ];

    /**
     * List of middleware aliases that should run on any before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     */
    public array $middleware = [];
}
