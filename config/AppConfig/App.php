<?php

namespace AppConfig;

use MVCME\Config\App as ConfigApp;

class App extends ConfigApp
{
    /**
     * Default port of application
     */
    public string $port = '9000';

    /**
     * Hostname of application root
     */
    public string $hostname = 'localhost';

    /**
     * URL to your application assets
     */
    public string $assetHostname = '/pts_cdn';

    /**
     * URL to your application API
     */
    public string $apiHostname = '/pts_api';

    /**
     * Hostname that is allowed to access the application
     */
    public array $allowedHostnames = [
        'api',
        'cdn'
    ];

    /**
     * The main file to run
     */
    public string $indexPage = 'index.php';

    /**
     * Application timezone
     */
    public string $timezone = 'UTC';

    /**
     * This determines which character set is used by default in various methods
     * that require a character set to be provided
     */
    public string $charset = 'UTF-8';

    /**
     * Make every http request done with a secure network
     */
    public bool $secureRequest = false;

    /**
     * Determine which server global should be used to retrieve the URI string
     */
    public string $uriProtocol = 'REQUEST_URI';
}
