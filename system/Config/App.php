<?php

namespace MVCME\Config;

class App
{
    /**
     * Default port of application
     */
    public string $port = '8080';

    /**
     * Hostname of application root
     */
    public string $hostname = 'localhost';

    /**
     * URL to your application root.
     * This will be your base URL. Must end with a slash ("/")
     */
    public string $baseURL;

    /**
     * Hostname to your application assets
     * 
     * How to use:
     * - "cdn" => "http://cdn.localhost/"
     * - "cdn.dummy" => "http://cdn.dummy/"
     * - "cdn.localhost:8000" => "http://cdn.localhost:8000/"
     * - "localhost/cdn" => "http://localhost/cdn/"
     */
    public string $assetHostname = 'cdn';

    /**
     * URL to your application assets
     * Must end with a slash ("/")
     */
    public string $assetURL;

    /**
     * Hostname to your application API
     * 
     * How to use:
     * - "api" => "http://api.localhost:8080"
     * - "api.localhost" => "http://api.localhost:8080"
     * - "api.localhost:8000" => "http://api.localhost:8000"
     */
    public string $apiHostname;

    /**
     * URL to your application API
     * Must end with a slash ("/")
     */
    public string $apiURL;

    /**
     * Hostname that is allowed to access the application
     * 
     * How to use:
     * - ["https://yourdomain.com"]
     */
    public array $allowedHostnames = [];

    /**
     * The main file to run
     */
    public string $indexPage = 'index.php';

    /**
     * Application timezone
     * 
     * The time zone the app will use.
     * This will affect the timestamp of each request to this application
     * @see https://www.php.net/manual/en/timezones.php for list of timezones supported by PHP.
     */
    public string $timezone = 'UTC';

    /**
     * This determines which character set is used by default in various methods
     * that require a character set to be provided
     */
    public string $charset = 'UTF-8';

    /**
     * Make every http request done with a secure network
     * 
     * If true, this will make it mandatory for every request to this application
     * to be made using the HTTPS method. Otherwise, it will cause every request to
     * this application to be made using HTTP and HTTPS methods
     */
    public bool $secureRequest = false;

    /**
     * Determine which server global should be used to retrieve the URI string.
     *
     * - REQUEST_URI = $_SERVER['REQUEST_URI']
     * - QUERY_STRING = $_SERVER['QUERY_STRING']
     * - PATH_INFO = $_SERVER['PATH_INFO']
     */
    public string $uriProtocol = 'REQUEST_URI';


    public function __construct()
    {
        $this->mergeEnv();
        $this->initialize();
    }

    /**
     * Port to ignore
     */
    private array $ignorePort = [
        80, 443
    ];

    /**
     * Initialize app props
     */
    private function initialize()
    {
        $scheme = $this->secureRequest ? 'https' : 'http';
        $this->baseURL = "{$scheme}://{$this->hostname}";

        if (!in_array($this->port, $this->ignorePort))
            $this->baseURL .= ":{$this->port}/";
    }

    /**
     * Combine Application configuration with Environment files.
     * Some configurations have to be set inside the App class
     * @return void
     */
    private function mergeEnv()
    {
        if (isset($_ENV['app']['port'])) $this->port = $_ENV['app']['port'];
        if (isset($_ENV['app']['hostname'])) $this->hostname = $_ENV['app']['hostname'];
        if (isset($_ENV['app']['secureRequest'])) $this->secureRequest = $_ENV['app']['secureRequest'];
    }
}
