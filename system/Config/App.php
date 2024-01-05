<?php

namespace MVCME\Config;

class App
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
     * URL to your application root.
     * This will be your base URL. Must end with a slash ("/")
     */
    public string $baseURL;

    /**
     * How to use:
     * - "cdn" => "http://cdn.localhost/"
     * - "cdn.dummy" => "http://cdn.dummy/"
     * - "/cdn" => "http://localhost/cdn/"
     * 
     * @var string URL to your application assets
     */
    public $assetHostname = 'cdn';

    /**
     * URL to your application assets
     * Must end with a slash ("/")
     */
    public string $assetURL;

    /**
     * How to use:
     * - "api" => "http://api.localhost/"
     * - "api.dummy" => "http://api.dummy/"
     * - "/api" => "http://localhost/api/"
     * 
     * @var string URL to your application API
     */
    public $apiHostname = 'api';

    /**
     * URL to your application API
     * Must end with a slash ("/")
     */
    public string $apiURL;

    /**
     * How to use:
     * - ["."] => ["https://yourdomain.com"]
     * - ["sub"] => "https://sub.yourdomain.com"
     * - ["sub.domain.com"] => "https://sub.domain.com"
     * - ["https://specific.domain.com"] => "https://specific.domain.com"
     * 
     * @var array Hostname that is allowed to access the application
     */
    public $allowedHostnames = [];

    /**
     * URLs that is allowed to access the application
     */
    public array $allowedURLs = [];

    /**
     * @var string The main file to run
     */
    public $indexPage = 'index.php';

    /**
     * The time zone the app will use.
     * This will affect the timestamp of each request to this application
     * @see https://www.php.net/manual/en/timezones.php for list of timezones supported by PHP.
     * 
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
     * If true, this will make it mandatory for every request to this application
     * to be made using the HTTPS method. Otherwise, it will cause every request to
     * this application to be made using HTTP and HTTPS methods
     * 
     * @var bool Make every http request done with a secure network
     */
    public $secureRequest = false;

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
     * Get ignored ports
     * @return array
     */
    public function getIgnorePort()
    {
        return $this->ignorePort;
    }


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

            if ($hostname == '.') {
                $url = $this->normalizeURI($this->baseURL);
                array_push($this->allowedURLs, $url);
                continue;
            }

            if (preg_match('/^(https?:\/\/[A-Za-z0-9_-]+)/i', $hostname)) {
                array_push($this->allowedURLs, $hostname);
                continue;
            }

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

        if (!empty($this->port) && !in_array($this->port, $this->ignorePort))
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

        if (!empty($this->port) && !in_array($this->port, $this->ignorePort))
            $hostname .= ":{$this->port}";

        if (preg_match('/^(https?:\/\/[A-Za-z0-9_-]+)/i', $url)) {
            // Format: ://<hostname> => http://hostname or https://hostname
            return $url;
        } elseif (preg_match('~^/[A-Za-z_-]+$~', $url)) {
            // Format: /<segment> => hostname/segment
            return "{$hostname}{$url}";
        } elseif (preg_match('~^[A-Za-z_-]+\.[A-Za-z_-]+$~', $url)) {
            // Format: <sub>.<domain> => sub.custom_domain.com
            return $url;
        } elseif (preg_match('~^[A-Za-z_-]+$~', $url)) {
            // Format: <sub> => sub.hostname
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
        if (isset($_ENV['ENVIRONMENT'])) $this->environment = $_ENV['ENVIRONMENT'];
        if (isset($_ENV['app']['port'])) $this->port = $_ENV['app']['port'];
        if (isset($_ENV['app']['hostname'])) $this->hostname = $_ENV['app']['hostname'];
        if (isset($_ENV['app']['secureRequest'])) $this->secureRequest = $_ENV['app']['secureRequest'];
    }
}
