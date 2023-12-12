<?php

namespace AppConfig;

use MVCME\Config\Routing as BaseRouting;

/**
 * Routing configuration
 */
class Routing extends BaseRouting
{
    /**
     * An array of files that contain route definitions.
     * Route files are read in order, with the first match
     * found taking precedence.
     *
     * Default: APPPATH . 'AppConfig/Routes.php'
     */
    public array $routeFiles = [
        CONFIGPATH . 'AppConfig/Routes.php',
    ];

    /**
     * The default namespace to use for Controllers when no other
     * namespace has been specified.
     *
     * Default: 'App\Controllers'
     */
    public string $defaultNamespace = 'App\Controllers';

    /**
     * The default controller to use when no other controller has been
     * specified.
     *
     * Default: 'Home'
     */
    public string $defaultController = 'Home';

    /**
     * The default method to call on the controller when no other
     * method has been set in the route.
     *
     * Default: 'index'
     */
    public string $defaultMethod = 'index';

    /**
     * Sets the class/method that should be called if routing doesn't
     * find a match. It can be either a closure or the controller/method
     * name exactly like a route is defined: Users::index
     *
     * This setting is passed to the Router class and handled there.
     *
     * If you want to use a closure, you will have to set it in the
     * class constructor or the routes file by calling:
     *
     * Route::setDefault404(function() {
     *    // Do something here
     * });
     *
     * Example:
     *  public $default404 = 'App\Errors::show404';
     */
    public ?string $default404 = null;
}
