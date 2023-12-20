<?php

namespace MVCME\Service;

use MVCME\MVCME;
use AppConfig\App;
use MVCME\Modules\Modules;
use MVCME\Config\Autoload;
use MVCME\Autoloader\Autoloader;
use MVCME\Autoloader\FileLocator;
use MVCME\Service\Services;
use MVCME\GlobalConstants;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Debug\Exceptions;
use CodeIgniter\Email\Email;
use CodeIgniter\Filters\Filters;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\SiteURIFactory;
use CodeIgniter\HTTP\URI;
use CodeIgniter\Pager\Pager;
use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Router\RouteCollectionInterface;
use CodeIgniter\Router\Router;
use CodeIgniter\Security\Security;
use CodeIgniter\Session\Session;
use CodeIgniter\Validation\ValidationInterface;
use CodeIgniter\View\RendererInterface;
use CodeIgniter\View\View;
use Config\Filters as ConfigFilters;
use Config\Format as ConfigFormat;
use Config\Images;
use Config\Migrations;
use Config\Pager as ConfigPager;
use Config\View as ConfigView;

// /**
//  * Services Configuration file.
//  *
//  * Services are simply other classes/libraries that the system uses
//  * to do its job. This is used by CodeIgniter to allow the core of the
//  * framework to be swapped out easily without affecting the usage within
//  * the rest of your application.
//  *
//  * This is used in place of a Dependency Injection container primarily
//  * due to its simplicity, which allows a better long-term maintenance
//  * of the applications built on top of CodeIgniter. A bonus side-effect
//  * is that IDEs are able to determine what class you are calling
//  * whereas with DI Containers there usually isn't a way for them to do this.
//  *
//  * Warning: To allow overrides by service providers do not use static calls,
//  * instead call out to \Config\Services (imported as AppServices).
//  *
//  * @see http://blog.ircmaxell.com/2015/11/simple-easy-risk-and-change.html
//  * @see http://www.infoq.com/presentations/Simple-Made-Easy
//  *
//  * @method static void                       createRequest(App $config, bool $isCli = false)
//  * @method static CURLRequest                curlrequest($options = [], ResponseInterface $response = null, App $config = null, $getShared = true)
//  * @method static Email                      email($config = null, $getShared = true)
//  * @method static Filters                    filters(ConfigFilters $config = null, $getShared = true)
//  * @method static IncomingRequest            incomingrequest(?App $config = null, bool $getShared = true)
//  * @method static Iterator                   iterator($getShared = true)
//  * @method static Pager                      pager(ConfigPager $config = null, RendererInterface $view = null, $getShared = true)
//  * @method static Parser                     parser($viewPath = null, ConfigView $config = null, $getShared = true)
//  * @method static RedirectResponse           redirectresponse(App $config = null, $getShared = true)
//  * @method static View                       renderer($viewPath = null, ConfigView $config = null, $getShared = true)
//  * @method static IncomingRequest|CLIRequest request(App $config = null, $getShared = true)
//  * @method static ResponseInterface          response(App $config = null, $getShared = true)
//  * @method static Router                     router(RouteCollectionInterface $routes = null, Request $request = null, $getShared = true)
//  * @method static RouteCollection            routes($getShared = true)
//  * @method static Security                   security(App $config = null, $getShared = true)
//  * @method static Session                    session(App $config = null, $getShared = true)
//  * @method static SiteURIFactory             siteurifactory(App $config = null, Superglobals $superglobals = null, $getShared = true)
//  * @method static GlobalConstants            globalcontstants(array $server = null, array $get = null, bool $getShared = true)
//  * @method static URI                        uri($uri = null, $getShared = true)
//  * @method static ValidationInterface        validation(ConfigValidation $config = null, $getShared = true)
//  */
class BaseService
{
    /**
     * Cache for instance of any services that have been requested as a "shared" instance.
     * Keys should be lowercase service names.
     * @var array
     */
    protected static $instances = [];

    /**
     * Mock objects for testing which are returned if exist
     * @var array
     */
    protected static $mocks = [];

    /**
     * Have we already discovered other Services?
     * @var bool
     */
    protected static $discovered = false;

    

    /**
     * Provides the ability to perform case-insensitive calling of service names
     * @return object|null
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $service = self::serviceExists($name);

        if ($service === null) {
            return null;
        }

        return $service::$name(...$arguments);
    }
}
