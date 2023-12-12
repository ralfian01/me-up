<?php

namespace AppRoutes\REST;

use MVCME\Config\Routing as BaseRouting;

/**
 * Routing configuration
 */
class Routing extends BaseRouting
{
    /**
     * An array of files that contain route definitions
     */
    public array $routeFiles = [
        ROUTESPATH . 'REST\Routes.php',
    ];

    /**
     * The default namespace to use for Controllers when no other namespace has been specified
     */
    public string $defaultNamespace = 'App\REST';

    /**
     * The default controller to use when no other controller has been specified
     */
    public string $defaultController = 'Home';

    /**
     * The default method to call on the controller when no other method has been set in the route
     */
    public string $defaultMethod = 'index';

    /**
     * Sets the class/method that should be called if routing doesn't
     * find a match.
     */
    public ?string $default404 = null;
}
