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
     * - "/cdn" => "http://localhost/cdn/"
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
     * - "api" => "http://api.localhost/"
     * - "api.dummy" => "http://api.dummy/"
     * - "/api" => "http://localhost/api/"
     */
    public string $apiHostname = 'api';

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
     * - ["sub"] => "https://sub.yourdomain.com"
     * - ["sub.domain.com"] => "https://sub.domain.com"
     */
    public array $allowedHostnames = [];

    /**
     * URLs that is allowed to access the application
     */
    public array $allowedURLs = [];

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
        $this->initializeURL();
        $this->initializeAllowedURL();
    }

    /**
     * Port to ignore
     */
    private array $ignorePort = [
        80, 443
    ];

    /**
     * Initialize app props
     * @return void
     */
    private function initializeURL()
    {
        $scheme = $this->secureRequest ? 'https' : 'http';

        $this->baseURL = "{$scheme}://" . $this->makeBaseURL();

        // Initialize asset URI
        $assetURL = $this->normalizeURI($this->assetHostname);
        $this->assetURL = $assetURL;

        // Initialize API URI
        $apiURL = $this->normalizeURI($this->apiHostname);
        $this->apiURL = $apiURL;
    }

    /**
     * Initialize Allowed URL from allowed hostnames
     * @return void
     */
    private function initializeAllowedURL()
    {
        foreach ($this->allowedHostnames as $hostname) {

            $url = $this->normalizeURI($hostname);
            array_push($this->allowedURLs, "http://{$url}");
            array_push($this->allowedURLs, "https://{$url}");
        }
    }

    /**
     * Make Base URL from $hostname
     * @return string
     */
    private function makeBaseURL()
    {
        $hostname = $this->hostname;

        if (!in_array($this->port, $this->ignorePort))
            $hostname .= ":{$this->port}";

        return $hostname;
    }

    /**
     * Normalize URI
     * @return string
     */
    private function normalizeURI(string $url)
    {
        $hostname = $this->hostname;

        if (!in_array($this->port, $this->ignorePort))
            $hostname .= ":{$this->port}";

        if (preg_match('~^/[A-Za-z_-]+$~', $url)) {
            return "{$hostname}{$url}";
        } elseif (preg_match('~^[A-Za-z_-]+\.[A-Za-z_-]+$~', $url)) {
            return $url;
        } elseif (preg_match('~^[A-Za-z_-]+$~', $url)) {
            return "{$url}.{$hostname}";
        } else {
            return $url;
        }
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
