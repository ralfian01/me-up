<?php

namespace MVCME\Service;

use MVCME\HTTP\CORS;
use MVCME\Config\App;
use MVCME\Config\Assets;
use MVCME\Asset\Asset;
use MVCME\URI\URI;
use MVCME\URI\URIBuilder;
use MVCME\Request\HTTPRequest;
use MVCME\Request\HTTPRequestInterface;
use MVCME\Response\HTTPResponse;
use MVCME\Response\HTTPResponseInterface;
use MVCME\Format\Format;
use MVCME\Router\Router;
use MVCME\Router\RoutePack;
use MVCME\Router\RoutePackInterface;
use MVCME\Middleware\Middleware;
use MVCME\GlobalConstants;
use AppConfig\App as AppConfig;
use AppConfig\Format as FormatConfig;
use AppConfig\Routing as RoutingConfig;
use AppConfig\Middleware as MiddlewareConfig;
use AppConfig\Assets as AssetConfig;
use AppConfig\Autoload as AppConfigAutoload;
use Throwable;

// use CodeIgniter\Email\Email;
// use CodeIgniter\HTTP\CURLRequest;
// use CodeIgniter\Images\Handlers\BaseHandler;
// use CodeIgniter\Pager\Pager;
// use CodeIgniter\Session\Handlers\Database\MySQLiHandler;
// use CodeIgniter\Session\Handlers\Database\PostgreHandler;
// use CodeIgniter\Session\Handlers\DatabaseHandler;
// use CodeIgniter\Session\Session;

use Config\Email as EmailConfig;
use Config\Images;
use Config\Pager as PagerConfig;
use MVCME\Autoloader\Autoloader;
use MVCME\Config\Autoload;

/**
 * Services Configuration file
 */
class Services
{
    /**
     * Cache for instance of any services that have been requested as a "shared" instance.
     * Keys should be lowercase service names.
     * @var array
     */
    protected static $instances = [];

    /**
     * Returns a shared instance of any of the class' services
     * $key must be a name matching a service
     * @param array|bool|float|int|object|string|null ...$params
     * @return object
     */
    protected static function getSharedInstance(string $key, ...$params)
    {
        $key = strtolower($key);

        if (!isset(self::$instances[$key])) {
            // Make sure $getShared is false
            $params[] = false;

            self::$instances[$key] = self::$key(...$params);
        }

        return self::$instances[$key];
    }

    /**
     * Autoload
     * @return Autoloader
     */
    public static function autoloader(?Autoload $autoloadConfig = null, bool $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__, $autoloadConfig);

        return new Autoloader(
            $autoloadConfig ?? new AppConfigAutoload
        );
    }

    /**
     * App Config
     * @return App
     */
    public static function appConfig(?App $config = null, bool $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__, $config);

        return new AppConfig();
    }

    /**
     * The URI class provides a way to model and manipulate URIs
     * @param string|null $uri The URI string
     * @param App|null $config
     * @return URI The current URI if $uri is null
     */
    public static function uri(?string $uri = null, $config = null, bool $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__, $uri, $config);

        if ($uri === null) {
            $uriBuilder = new URIBuilder(
                $config ?? self::appConfig(),
                self::globalConstants()
            );

            return $uriBuilder->createFromGlobals();
        }

        return new URI($uri);
    }

    /**
     * Returns the current Request object
     * @return HTTPRequestInterface
     */
    public static function request(?URI $uri = null, bool $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__, $uri);

        return new HTTPRequest(
            $uri ?? self::uri()
        );
    }

    /**
     * The Response class models an HTTP response
     * @return HTTPResponseInterface
     */
    public static function response(bool $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__);

        return new HTTPResponse();
    }

    /**
     * GlobalConstants
     * @return GlobalConstants
     */
    public static function globalConstants(?array $server = null, ?array $get = null, bool $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__, $server, $get);

        return new GlobalConstants($server, $get);
    }

    /**
     * The Format class is a convenient place to create Formatters
     * @return Format
     */
    public static function format(?FormatConfig $config = null, bool $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__, $config);

        return new Format(
            $config ?? new FormatConfig
        );
    }

    /**
     * The Routes service is a class that allows for easily building a collection of routes
     * @return RoutePackInterface
     */
    public static function routes(?RoutingConfig $routing = null, $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__, $routing);

        return new RoutePack(
            $routing ?? new RoutingConfig
        );
    }

    /**
     * The Router class uses a RouteCollection's array of routes, and determines
     * the correct Controller and Method to execute
     * @return Router
     */
    public static function router(?RoutePack $routes = null, ?HTTPRequest $request = null, bool $shared = true)
    {
        if ($shared)
            return self::getSharedInstance(__FUNCTION__, $routes, $request);

        return new Router(
            $routes ?? self::routes(),
            $request ?? self::request()
        );
    }

    /**
     * Middleware allow you to run tasks before and/or after a controller is executed.
     * @return Middleware
     */
    public static function middleware(?MiddlewareConfig $config = null, bool $shared = true)
    {
        if ($shared)
            return static::getSharedInstance(__FUNCTION__, $config);

        return new Middleware(
            $config ?? new MiddlewareConfig,
            self::request(),
            self::response()
        );
    }

    /**
     * @return Asset
     */
    public static function assets(?App $config = null, ?Assets $asset = null, ?RoutePackInterface $routes = null, $shared = true)
    {
        if ($shared)
            return static::getSharedInstance(__FUNCTION__, $config, $asset, $routes);

        return new Asset(
            $config ?? self::appConfig(),
            $asset ?? new AssetConfig,
            $routes ?? self::routes()
        );
    }

    /**
     * Set header CORS
     * @return CORS
     */
    public static function CORS(?App $config = null, ?GlobalConstants $globalConstants = null, $shared = true)
    {
        if ($shared)
            return static::getSharedInstance(__FUNCTION__, $config, $globalConstants);

        return new CORS(
            $config ?? self::appConfig(),
            $globalConstants ?? self::globalConstants()
        );
    }

    /**
     * Normalize the URI with the hostname and port that have been set in the application configuration
     * @return string
     */
    public static function normalizeURI(string $url, ?App $config = null)
    {
        $config ??= self::appConfig();
        $hostname = $config->hostname;

        if (!empty($config->port) && !in_array($config->port, $config->getIgnorePort()))
            $hostname .= ":{$config->port}";

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
     * Print error trace
     * @param Throwable $error
     */
    public static function traceError(?Throwable $throw)
    {
        $error = [
            'message' => $throw->getMessage(),
            'trace' => null
        ];

        // Breakdown trace
        $parts = explode('#', $throw->getTraceAsString());

        $trace = [];
        foreach ($parts as $part) {
            $trace[] = '#' . trim($part);
        }

        array_shift($trace);

        $error['trace'] = $trace;

        return $error;
    }

    // /**
    //  * The CURL Request class acts as a simple HTTP client for interacting with other servers, typically through APIs
    //  * @return CURLRequest
    //  */
    // public static function curlrequest(array $options = [], ?ResponseInterface $response = null, ?App $config = null, bool $getShared = true)
    // {
    //     if ($getShared === true) {
    //         return static::getSharedInstance('curlrequest', $options, $response, $config);
    //     }

    //     $config ??= new AppConfig;
    //     $response ??= new Response($config);

    //     // return new CURLRequest(
    //     //     $config,
    //     //     new URI($options['base_uri'] ?? null),
    //     //     $response,
    //     //     $options
    //     // );
    // }

    // /**
    //  * The Email class allows you to send email via mail, sendmail, SMTP.
    //  *
    //  * @param array|EmailConfig|null $config
    //  *
    //  * @return Email
    //  */
    // public static function email($config = null, bool $getShared = true)
    // {
    //     if ($getShared) {
    //         return static::getSharedInstance('email', $config);
    //     }

    //     if (empty($config) || !(is_array($config) || $config instanceof EmailConfig)) {
    //         $config = config(EmailConfig::class);
    //     }

    //     return new Email($config);
    // }

    // /**
    //  * Acts as a factory for ImageHandler classes and returns an instance
    //  * of the handler. Used like Services::image()->withFile($path)->rotate(90)->save();
    //  * @return BaseHandler
    //  */
    // public static function image(?string $handler = null, ?Images $config = null, bool $getShared = true)
    // {
    //     if ($getShared) {
    //         return static::getSharedInstance('image', $handler, $config);
    //     }

    //     $config ??= config(Images::class);
    //     assert($config instanceof Images);

    //     $handler = $handler ?: $config->defaultHandler;
    //     $class   = $config->handlers[$handler];

    //     return new $class($config);
    // }

    // /**
    //  * Return the appropriate pagination handler
    //  * @return Pager
    //  */
    // public static function pager(?PagerConfig $config = null, ?RendererInterface $view = null, bool $getShared = true)
    // {
    //     if ($getShared) {
    //         return static::getSharedInstance('pager', $config, $view);
    //     }

    //     $config ??= config(PagerConfig::class);
    //     $view ??= AppServices::renderer(null, null, false);

    //     return new Pager($config, $view);
    // }
}
