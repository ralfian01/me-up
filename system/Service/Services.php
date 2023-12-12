<?php

namespace MVCME\Service;

use MVCME\Config\App;
use MVCME\Config\Routing;
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
use MVCME\GlobalConstants;
use AppConfig\App as AppConfig;
use AppConfig\Format as FormatConfig;
use AppConfig\Routing as RoutingConfig;

use CodeIgniter\Email\Email;
use CodeIgniter\Filters\Filters;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Images\Handlers\BaseHandler;
use CodeIgniter\Pager\Pager;
// use CodeIgniter\Session\Handlers\Database\MySQLiHandler;
// use CodeIgniter\Session\Handlers\Database\PostgreHandler;
// use CodeIgniter\Session\Handlers\DatabaseHandler;
// use CodeIgniter\Session\Session;

use CodeIgniter\View\RendererInterface;
use CodeIgniter\View\View;

use Config\Database;
use Config\Email as EmailConfig;
use Config\Filters as FiltersConfig;
use Config\Images;
use Config\Pager as PagerConfig;
use Config\Paths;
use Config\View as ViewConfig;

/**
 * Services Configuration file
 */
class Services extends BaseService
{
    /**
     * The URI class provides a way to model and manipulate URIs
     * @param string|null $uri The URI string
     * @return URI The current URI if $uri is null
     */
    public static function uri(?string $uri = null, ?App $config = null)
    {
        if ($uri === null)
            return (new URIBuilder(
                $config ?? new AppConfig,
                new GlobalConstants
            ))->createFromGlobals();

        return new URI($uri);
    }

    /**
     * Returns the current Request object
     * @return HTTPRequestInterface
     */
    public static function request()
    {
        return new HTTPRequest(
            self::uri()
        );
    }

    /**
     * The Response class models an HTTP response
     * @return HTTPResponseInterface
     */
    public static function response()
    {
        return new HTTPResponse();
    }

    /**
     * GlobalConstants
     * @return GlobalConstants
     */
    public static function globalContants(?array $server = null, ?array $get = null)
    {
        return new GlobalConstants($server, $get);
    }

    /**
     * The Format class is a convenient place to create Formatters
     * @return Format
     */
    public static function format(?FormatConfig $config = null)
    {
        return new Format($config ?? new FormatConfig);
    }

    /**
     * The Routes service is a class that allows for easily building a collection of routes
     * @return RoutePackInterface
     */
    public static function routes(?Routing $routing = null)
    {
        return new RoutePack(
            $routing ?? new RoutingConfig
        );
    }

    /**
     * The Router class uses a RouteCollection's array of routes, and determines
     * the correct Controller and Method to execute
     * @return Router
     */
    public static function router(?RoutePack $routes = null, ?HTTPRequest $request = null)
    {
        return new Router(
            $routes ?? self::routes(),
            $request ?? self::request()
        );
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
    //  * Filters allow you to run tasks before and/or after a controller
    //  * is executed. During before filters, the request can be modified,
    //  * and actions taken based on the request, while after filters can
    //  * act on or modify the response itself before it is sent to the client
    //  * @return Filters
    //  */
    // public static function filters(?FiltersConfig $config = null, bool $getShared = true)
    // {
    //     if ($getShared) {
    //         return static::getSharedInstance('filters', $config);
    //     }

    //     $config ??= config(FiltersConfig::class);

    //     return new Filters($config, self::request(), self::response());
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

    // /**
    //  * The Renderer class is the class that actually displays a file to the user.
    //  * The default View class within CodeIgniter is intentionally simple, but this
    //  * service could easily be replaced by a template engine if the user needed to
    //  * @return View
    //  */
    // public static function renderer(?string $viewPath = null, ?ViewConfig $config = null, bool $getShared = true)
    // {
    //     if ($getShared) {
    //         return static::getSharedInstance('renderer', $viewPath, $config);
    //     }

    //     $viewPath = $viewPath ?: (new Paths())->viewDirectory;
    //     $config ??= config(ViewConfig::class);

    //     return new View($config, $viewPath, AppServices::locator(), CI_DEBUG, AppServices::logger());
    // }

    // /**
    //  * The Redirect class provides nice way of working with redirects
    //  * @return RedirectResponse
    //  */
    // public static function redirectresponse(?App $config = null, bool $getShared = true)
    // {
    //     if ($getShared) {
    //         return static::getSharedInstance('redirectresponse', $config);
    //     }

    //     $config ??= config(App::class);
    //     $response = new RedirectResponse($config);
    //     $response->setProtocolVersion(AppServices::request()->getProtocolVersion());

    //     return $response;
    // }

    // // /**
    // //  * The Validation class provides tools for validating input data
    // //  * @return ValidationInterface
    // //  */
    // // public static function validation(?ValidationConfig $config = null, bool $getShared = true)
    // // {
    // //     if ($getShared) {
    // //         return static::getSharedInstance('validation', $config);
    // //     }

    // //     $config ??= config(ValidationConfig::class);

    // //     return new Validation($config, AppServices::renderer());
    // // }
}
