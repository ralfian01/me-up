<?php

namespace MVCME\Router;

use MVCME\Request\HTTPRequest;
use Exception;
use Closure;

/**
 * Request router
 */
class Router
{
    /**
     * A RouteCollection instance
     * @var RoutePack
     */
    protected $pack;

    /**
     * Sub-directory that contains the requested controller class.
     * Primarily used by 'autoRoute'
     * @var string|null
     */
    protected $directory;

    /**
     * The name of the controller class
     * @var Closure|string
     */
    protected $controller;

    /**
     * The name of the method to use
     * @var string
     */
    protected $method;

    /**
     * An array of binds that were collected so they can be sent to closure routes
     * @var array
     */
    protected $params = [];

    /**
     * The name of the front controller
     * @var string
     */
    protected $indexPage = 'index.php';

    /**
     * The route that was matched for this request.
     *
     * @var array|null
     */
    protected $matchedRoute;

    /**
     * The options set for the matched route
     * @var array|null
     */
    protected $matchedRouteOptions;

    /**
     * The filter info from Route Collection if the matched route should be filtered.
     * @var string[]
     */
    protected $middlewareInfo = [];

    protected ?AutoRouterInterface $autoRouter = null;

    /**
     * Stores a reference to the RouteCollection object.
     */
    public function __construct(RoutePackInterface $routes, ?HTTPRequest $request = null)
    {
        $this->pack = $routes;

        // These are only for auto-routing
        $this->controller = $this->pack->getDefaultController();
        $this->method = $this->pack->getDefaultMethod();

        $this->pack->setHTTPVerb($request->getMethod() ?? $_SERVER['REQUEST_METHOD']);
    }

    /**
     * Finds the controller method corresponding to the URI
     * @param string|null $uri URI path relative to baseURL
     * @return Closure|string Controller classname or Closure
     */
    public function handle(?string $uri = null)
    {
        // If we cannot find a URI to match against, then set it to root (`/`).
        if ($uri === null || $uri === '')
            $uri = '/';

        // Decode URL-encoded string
        $uri = urldecode($uri);

        // Restart middlewareInfo
        $this->middlewareInfo = [];

        // Checks defined routes
        if ($this->checkRoutes($uri)) {

            if ($this->pack->isFiltered($this->matchedRoute[0]))
                $this->middlewareInfo = $this->pack->getMiddlewareForRoute($this->matchedRoute[0]);

            return $this->controller;
        }

        // // Still here? Then we can try to match the URI against
        // // Controllers/directories, but the application may not
        // // want this, like in the case of API's.
        // if (!$this->pack->shouldAutoRoute()) {
        //     throw new PageNotFoundException(
        //         "Can't find a route for '{$this->pack->getHTTPVerb()}: {$uri}'."
        //     );
        // }

        return $this->controllerName();
    }

    /**
     * Returns the filter info for the matched route, if any
     * @return string[]
     */
    public function getMiddleware()
    {
        return $this->middlewareInfo;
    }

    /**
     * Returns the name of the matched controller
     * @return Closure|string Controller classname or Closure
     */
    public function controllerName()
    {
        return $this->controller;
    }

    /**
     * Returns the name of the method to run in the chosen container
     * @return string
     */
    public function methodName()
    {
        return $this->method;
    }

    /**
     * Returns the 404 Override settings from the Collection.
     * If the override is a string, will split to controller/index array.
     */
    public function getDefault404()
    {
        $route = $this->pack->getDefault404();

        if (is_string($route)) {
            $routeArray = explode('::', $route);

            return [
                $routeArray[0], // Controller
                $routeArray[1] ?? 'index',   // Method
            ];
        }

        if (is_callable($route)) {
            return $route;
        }

        return null;
    }

    /**
     * Returns the binds that have been matched and collected
     * during the parsing process as an array, ready to send to
     * instance->method(...$params).
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }

    /**
     * Returns the name of the sub-directory the controller is in,
     * if any. Relative to APPPATH.'Controllers'
     * @return string
     */
    public function directory(): string
    {
        if ($this->autoRouter instanceof AutoRouter) {
            return $this->autoRouter->directory();
        }

        return '';
    }

    /**
     * Returns the routing information that was matched for this request, if a route was defined
     * @return array|null
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    /**
     * Returns all options set for the matched route
     * @return array|null
     */
    public function getMatchedRouteOptions()
    {
        return $this->matchedRouteOptions;
    }

    /**
     * Sets the value that should be used to match the index.php file. Defaults
     * to index.php but this allows you to modify it in case you are using
     * something like mod_rewrite to remove the page. This allows you to set
     * it a blank
     * @param string $page
     * @return $this
     */
    public function setIndexPage($page): self
    {
        $this->indexPage = $page;

        return $this;
    }

    /**
     * Checks Defined Routes
     * @param string $uri The URI path to compare against the routes
     * @return bool Whether the route was matched or not
     */
    protected function checkRoutes(string $uri)
    {
        $routes = $this->pack->getRoutes($this->pack->getHTTPVerb());

        // Don't waste any time
        if (empty($routes))
            return false;

        $uri = $uri === '/' ? $uri : trim($uri, '/ ');

        // Loop through the route array looking for wildcards
        foreach ($routes as $routeKey => $handler) {
            $routeKey = $routeKey === '/'
                ? $routeKey
                : ltrim($routeKey, '/ ');

            $matchedKey = $routeKey;

            // Does the RegEx match?
            if (preg_match('#^' . $routeKey . '$#u', $uri, $matches)) {

                // // // Is this route supposed to redirect to another?
                // // if ($this->pack->isRedirect($routeKey)) {
                // //     // replacing matched route groups with references: post/([0-9]+) -> post/$1
                // //     $redirectTo = preg_replace_callback('/(\([^\(]+\))/', static function () {
                // //         static $i = 1;

                // //         return '$' . $i++;
                // //     }, is_array($handler) ? key($handler) : $handler);

                // //     throw new RedirectException(
                // //         preg_replace('#^' . $routeKey . '$#u', $redirectTo, $uri),
                // //         $this->pack->getRedirectCode($routeKey)
                // //     );
                // // }
                // // // Store our locale so CodeIgniter object can
                // // // assign it to the Request.
                // // if (strpos($matchedKey, '{locale}') !== false) {
                // //     preg_match(
                // //         '#^' . str_replace('{locale}', '(?<locale>[^/]+)', $matchedKey) . '$#u',
                // //         $uri,
                // //         $matched
                // //     );

                // //     if (
                // //         $this->pack->shouldUseSupportedLocalesOnly()
                // //         && !in_array($matched['locale'], config(App::class)->supportedLocales, true)
                // //     ) {
                // //         // Throw exception to prevent the autorouter, if enabled,
                // //         // from trying to find a route
                // //         throw PageNotFoundException::forLocaleNotSupported($matched['locale']);
                // //     }

                // //     $this->detectedLocale = $matched['locale'];
                // //     unset($matched);
                // // }

                // Are we using Closures? If so, then we need
                // to collect the params into an array
                // so it can be passed to the controller method later.
                if (!is_string($handler) && is_callable($handler)) {

                    $this->controller = $handler;

                    // Remove the original string from the matches array
                    array_shift($matches);

                    $this->params = $matches;

                    $this->setMatchedRoute($matchedKey, $handler);

                    return true;
                }

                [$controller] = explode('::', $handler);

                // Check if controller is invalid
                if (strpos($controller, '/') !== false)
                    throw new Exception("Controller name is invalid. Controller name: \"{$controller}\"");

                if (strpos($handler, '$') !== false && strpos($routeKey, '(') !== false) {
                    // Checks dynamic controller
                    if (strpos($controller, '$') !== false) {
                        throw new Exception("Invalid dynamic Controller");
                    }

                    // Using back-references
                    $handler = preg_replace('#^' . $routeKey . '$#u', $handler, $uri);
                }

                $this->setRequest(explode('/', $handler));

                $this->setMatchedRoute($matchedKey, $handler);

                return true;
            }
        }

        return false;
    }

    /**
     * Checks Auto Routes.
     * Attempts to match a URI path against Controllers and directories
     * found in APPPATH/Controllers, to find a matching route
     * @return void
     */
    public function autoRoute(string $uri)
    {
        [$this->directory, $this->controller, $this->method, $this->params]
            = $this->autoRouter->getRoute($uri, $this->pack->getHTTPVerb());
    }

    /**
     * Scans the controller directory, attempting to locate a controller matching the supplied uri $segments
     * @param array $segments URI segments
     * @return array returns an array of remaining uri segments that don't map onto a directory
     *
     * @deprecated Not used. Moved to AutoRouter class.
     */
    protected function scanControllers(array $segments): array
    {
        $segments = array_filter($segments, static fn ($segment) => $segment !== '');
        // numerically reindex the array, removing gaps
        $segments = array_values($segments);

        // if a prior directory value has been set, just return segments and get out of here
        if (isset($this->directory)) {
            return $segments;
        }

        // Loop through our segments and return as soon as a controller
        // is found or when such a directory doesn't exist
        $c = count($segments);

        while ($c-- > 0) {
            // $segmentConvert = ucfirst($this->translateURIDashes === true ? str_replace('-', '_', $segments[0]) : $segments[0]);
            // // as soon as we encounter any segment that is not PSR-4 compliant, stop searching
            // if (!$this->isValidSegment($segmentConvert)) {
            //     return $segments;
            // }

            // $test = APPPATH . 'Controllers/' . $this->directory . $segmentConvert;

            // // as long as each segment is *not* a controller file but does match a directory, add it to $this->directory
            // if (!is_file($test . '.php') && is_dir($test)) {
            //     $this->setDirectory($segmentConvert, true, false);
            //     array_shift($segments);

            //     continue;
            // }

            return $segments;
        }

        // This means that all segments were actually directories
        return $segments;
    }

    /**
     * Returns true if the supplied $segment string represents a valid PSR-4 compliant namespace/directory segment
     * regex comes from https://www.php.net/manual/en/language.variables.basics.php
     * @deprecated Moved to AutoRouter class.
     */
    private function isValidSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $segment);
    }

    /**
     * Set request route.
     * Takes an array of URI segments as input and sets the class/method
     * to be called.
     * @param array $segments URI segments
     * @return void
     */
    protected function setRequest(array $segments = [])
    {
        // If we don't have any segments - use the default controller;
        if (empty($segments)) {
            return;
        }

        [$controller, $method] = array_pad(explode('::', $segments[0]), 2, null);

        $this->controller = $controller;

        // $this->method already contains the default method name,
        // so don't overwrite it with emptiness.
        if (!empty($method)) {
            $this->method = $method;
        }

        array_shift($segments);

        $this->params = $segments;
    }

    /**
     * @param callable|string $handler
     * @return void
     */
    protected function setMatchedRoute(string $route, $handler)
    {
        $this->matchedRoute = [$route, $handler];

        $this->matchedRouteOptions = $this->pack->getRoutesOptions($route);
    }
}
