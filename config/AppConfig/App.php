<?php

namespace AppConfig;

use MVCME\Config\App as ConfigApp;

class App extends ConfigApp
{
    /**
     * @var string Project environment mode
     */
    public $environment = 'development';

    /**
     * @var int|null Default port of application
     */
    public $port = 9090;

    /**
     * @var string Hostname of application root
     */
    public $hostname = 'localhost';

    /**
     * @var string URL to your application assets
     */
    public $assetHostname = '/pts_cdn';

    /**
     * @var string URL to your application API
     */
    public $apiHostname = '/pts_api';

    /**
     * @var array Hostname that is allowed to access the application
     */
    public $allowedHostnames = [
        'api',
        'cdn'
    ];

    /**
     * @var string The main file to run
     */
    public $indexPage = 'index.php';

    /**
     * @var string Application timezone
     */
    public $timezone = 'UTC';

    /**
     * @var string Charset
     * 
     * This determines which character set is used by default in various methods
     * that require a character set to be provided
     */
    public $charset = 'UTF-8';

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

    /**
     * @var bool Make every http request done with a secure network
     */
    public $secureRequest = false;
}
