<?php

namespace MVCME\Router;

use MVCME\Config\Routing;
use MVCME\Service\Services;
use Closure;
use Exception;
use InvalidArgumentException;

/**
 * 
 */
class RoutePack implements RoutePackInterface
{
    /**
     * The namespace to be added to any Controllers.
     * This must have a trailing backslash (\).
     * @var string
     */
    protected $defaultNamespace = '\\';

    /**
     * The name of the default controller to use when no other controller is specified.
     * @var string
     */
    protected $defaultController = 'Home';

    /**
     * The name of the default method to use when no other method has been specified.     *
     * @var string
     */
    protected $defaultMethod = 'index';

    /**
     * A callable that will be shown when the route cannot be matched.
     * @var Closure|string
     */
    protected $default404;

    /**
     * An array of files that would contain route definitions.
     */
    protected array $routeFiles = [];

    /**
     * Defined placeholders that can be used within the
     * @var array<string, string>
     */
    protected $placeholders = [
        'any' => '.*',
        'segment' => '[^/]+',
        'alphanum' => '[a-zA-Z0-9]+',
        'num' => '[0-9]+',
        'alpha' => '[a-zA-Z]+',
    ];

    /**
     * An array of all routes and their mapping
     * @var array
     *
     * [
     *     verb => [
     *         routeKey(regex) => [
     *             'name'    => routeName
     *             'handler' => handler,
     *             'from'    => from,
     *         ],
     *     ],
     *     // redirect route
     *     '*' => [
     *          routeKey(regex)(from) => [
     *             'name'     => routeName
     *             'handler'  => [routeKey(regex)(to) => handler],
     *             'from'     => from,
     *             'redirect' => statusCode,
     *         ],
     *     ],
     * ]
     */
    protected $routes = [
        '*' => [],
        'get' => [],
        'post' => [],
        'put' => [],
        'delete' => [],
        'patch' => [],
    ];

    /**
     * Array of routes names
     * @var array
     *
     * [
     *     verb => [
     *         routeName => routeKey(regex)
     *     ],
     * ]
     */
    protected $routesNames = [
        '*' => [],
        'get' => [],
        'post' => [],
        'put' => [],
        'delete' => [],
        'patch' => [],
    ];

    /**
     * Array of routes options
     * @var array
     *
     * [
     *     verb => [
     *         routeKey(regex) => [
     *             key => value,
     *         ]
     *     ],
     * ]
     */
    protected $routesOptions = [];

    /**
     * The current method that the script is being called by.
     * @var string HTTP verb (lower case) like `get`,`post` or `*`
     */
    protected $HTTPVerb = '*';

    /**
     * The default list of HTTP methods that is allowed if no other method is provided.
     * @var array
     */
    protected $defaultHTTPMethods = [
        'get',
        'post',
        'put',
        'delete',
        'patch',
    ];

    /**
     * The name of the current group
     * @var string|null
     */
    protected $group;

    /**
     * The current subdomain
     * @var string|null
     */
    protected $currentSubdomain;

    /**
     * Stores copy of current options being applied during creation
     * @var array|null
     */
    protected $currentOptions;

    /**
     * The current hostname from $_SERVER['HTTP_HOST']
     * @var string
     */
    private ?string $httpHost = null;


    /**
     * Constructor
     */
    public function __construct(Routing $routing)
    {
        $this->httpHost = Services::request()->getServer('HTTP_HOST');

        // Setup based on config file. Let routes file override.
        $this->defaultNamespace = rtrim($routing->defaultNamespace, '\\') . '\\';
        $this->defaultController = $routing->defaultController;
        $this->defaultMethod = $routing->defaultMethod;
        $this->default404 = $routing->default404;
        $this->routeFiles = $routing->routeFiles;

        // Normalize the path string in routeFiles array
        foreach ($this->routeFiles as $rtKey => &$routeFile) {
            $this->routeFiles[$rtKey] = $this->normalizeRouteFiles($routeFile);
        }
    }

    /**
     * Normalize the path string and extension
     * @return void
     */
    private function normalizeRouteFiles(string $routeFiles)
    {
        $realpath = realpath($routeFiles);
        $realpath = $this->fixExtension($realpath);

        return ($realpath === false) ? $routeFiles : $realpath;
    }

    /**
     * Fix string route file extension
     * @return void
     */
    private function fixExtension(string $path)
    {
        $paths = explode('.', $path);
        foreach ($paths as $pathKey => &$path) {
            if ($pathKey >= 1)
                $path = strtolower($path);
        }

        return implode('.', $paths);
    }

    /**
     * Loads main routes file and discover routes
     * @return $this
     */
    public function loadRoutes(?string $routeFile = null)
    {
        $routeFiles = $this->routeFiles;

        if ($routeFile !== null) {
            $routeFile = $this->normalizeRouteFiles($routeFile);

            // Include the passed in routesFile if it doesn't exist.
            if (!in_array($routeFile, $routeFiles, true)) {
                $routeFiles[] = $routeFile;
            }
        }

        // We need this var in local scope so route files can access it.
        $routes = $this;

        foreach ($routeFiles as $routeFile) {
            if (!is_file($routeFile) || !file_exists($routeFile))
                continue;

            require $routeFile;
        }

        return $this;
    }

    /**
     * Registers a new constraint with the system. Constraints are used
     * by the routes as placeholders for regular expressions to make defining
     * the routes more human-friendly
     *
     * @param array|string $placeholder
     */
    public function addPlaceholder($placeholder, ?string $pattern = null)
    {
        if (!is_array($placeholder)) {
            $placeholder = [$placeholder => $pattern];
        }

        $this->placeholders = array_merge($this->placeholders, $placeholder);

        return $this;
    }

    /**
     * For `spark routes`
     * @return array<string, string>
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * Returns the 404 Override setting, which can be null, a closure or the controller/string.
     * @return Closure|string|null
     */
    public function getDefault404()
    {
        return $this->default404;
    }

    /**
     * Sets the class/method that should be called if routing doesn't
     * find a match. It can be either a closure or the controller/method
     * name exactly like a route is defined: Users::index
     * @param callable|string|null $callable
     * @return $this
     */
    public function setDefault404($callable = null)
    {
        $this->default404 = $callable;

        return $this;
    }

    /**
     * Returns the name of the default controller
     * @return string
     */
    public function getDefaultController()
    {
        return $this->defaultController;
    }

    /**
     * Sets the default controller to use when no other controller has been specified
     * @return $this
     */
    public function setDefaultController(string $value)
    {
        $this->defaultController = esc(strip_tags($value));

        return $this;
    }

    /**
     * Returns the name of the default method to use within the controller.
     * @return string
     */
    public function getDefaultMethod()
    {
        return $this->defaultMethod;
    }

    /**
     * Sets the default method to call on the controller when no other method has been set in the route.
     * @return $this
     */
    public function setDefaultMethod(string $value)
    {
        $this->defaultMethod = esc(strip_tags($value));

        return $this;
    }

    /**
     * Returns the default namespace as set in the Routes config file.
     * @return string
     */
    public function getDefaultNamespace()
    {
        return $this->defaultNamespace;
    }

    /**
     * Sets the default namespace to use for Controllers when no other namespace has been specified
     * @return $this
     */
    public function setDefaultNamespace(string $value)
    {
        $this->defaultNamespace = esc(strip_tags($value));
        $this->defaultNamespace = rtrim($this->defaultNamespace, '\\') . '\\';

        return $this;
    }

    /**
     * Returns the current HTTP Verb being used.
     * @return string
     */
    public function getHTTPVerb()
    {
        return $this->HTTPVerb;
    }

    /**
     * Sets the current HTTP verb
     * @param string $verb HTTP verb
     * @return $this
     */
    public function setHTTPVerb(string $verb)
    {
        $this->HTTPVerb = strtolower($verb);

        return $this;
    }
    /**
     * Returns the raw array of available routes.
     * @param bool $includeWildcard Whether to include '*' routes.
     * @return array
     */
    public function getRoutes(?string $verb = null, bool $includeWildcard = true)
    {
        if (empty($verb))
            $verb = $this->getHTTPVerb();

        $routes = [];

        if (isset($this->routes[$verb])) {
            // Keep current verb's routes at the beginning, so they're matched
            // before any of the generic, "add" routes.
            $collection = $includeWildcard ? $this->routes[$verb] + ($this->routes['*'] ?? []) : $this->routes[$verb];

            foreach ($collection as $routeKey => $r) {
                $routes[$routeKey] = $r['handler'];
            }
        }

        return $routes;
    }

    /**
     * Returns one or all routes options
     * @return array<string, int|string> [key => value]
     */
    public function getRoutesOptions(?string $from = null, ?string $verb = null)
    {
        $options = $this->loadRoutesOptions($verb);

        return $from ? $options[$from] ?? [] : $options;
    }

    /**
     * Group a series of routes under a single URL segment. This is handy for grouping items into an admin area, like
     *
     * @param string $name The name to group/prefix the routes with.
     * @param array|callable ...$params
     * @return void
     */
    public function group(string $name, ...$params)
    {
        $oldGroup = $this->group;
        $oldOptions = $this->currentOptions;

        // To register a route, we'll set a flag so that our router
        // will see the group name.
        // If the group name is empty, we go on using the previously built group name.
        $this->group = $name ? trim($oldGroup . '/' . $name, '/') : $oldGroup;

        $callback = array_pop($params);

        if ($params && is_array($params[0])) {
            $this->currentOptions = array_shift($params);
        }

        if (is_callable($callback)) {
            $callback($this);
        }

        $this->group = $oldGroup;
        $this->currentOptions = $oldOptions;
    }

    /**
     * Specifies a single route to match for multiple HTTP Verbs.
     *
     * Example:
     *  $route->match( ['get', 'post'], 'users/(:num)', 'users/$1);
     *
     * @param array|Closure|string $to
     */
    public function match(array $verbs = [], string $from = '', $to = '', ?array $options = null)
    {
        if (empty($from) || empty($to)) {
            throw new InvalidArgumentException('You must supply the parameters: from, to.');
        }

        foreach ($verbs as $verb) {
            $verb = strtolower($verb);

            $this->{$verb}($from, $to, $options);
        }

        return $this;
    }

    /**
     * Specifies a route that is only available to GET requests
     * @param array|Closure|string $to
     * @return $this
     */
    public function get(string $from, $to, ?array $options = null)
    {
        $this->create('get', $from, $to, $options);

        return $this;
    }

    /**
     * Specifies a route that is only available to POST requests
     * @param array|Closure|string $to
     */
    public function post(string $from, $to, ?array $options = null)
    {
        $this->create('post', $from, $to, $options);

        return $this;
    }

    /**
     * Specifies a route that is only available to PUT requests
     * @param array|Closure|string $to
     */
    public function put(string $from, $to, ?array $options = null)
    {
        $this->create('put', $from, $to, $options);

        return $this;
    }

    /**
     * Specifies a route that is only available to DELETE requests
     * @param array|Closure|string $to
     */
    public function delete(string $from, $to, ?array $options = null)
    {
        $this->create('delete', $from, $to, $options);

        return $this;
    }

    /**
     * Specifies a route that is only available to HEAD requests
     * @param array|Closure|string $to
     */
    public function head(string $from, $to, ?array $options = null)
    {
        $this->create('head', $from, $to, $options);

        return $this;
    }

    /**
     * Specifies a route that is only available to PATCH requests
     * @param array|Closure|string $to
     */
    public function patch(string $from, $to, ?array $options = null)
    {
        $this->create('patch', $from, $to, $options);

        return $this;
    }

    /**
     * Specifies a route that is only available to OPTIONS requests
     * @param array|Closure|string $to
     */
    public function options(string $from, $to, ?array $options = null)
    {
        $this->create('options', $from, $to, $options);

        return $this;
    }

    /**
     * Checks a route (using the "from") to see if it's filtered or not.
     */
    public function isFiltered(string $search, ?string $verb = null)
    {
        $options = $this->loadRoutesOptions($verb);

        return isset($options[$search]['middleware']);
    }

    /**
     * Returns the filters that should be applied for a single route, along
     * with any parameters it might have. Parameters are found by splitting
     * the parameter name on a colon to separate the filter name from the parameter list,
     * and the splitting the result on commas. So:
     *
     *    'role:admin,manager'
     *
     * has a filter of "role", with parameters of ['admin', 'manager'].
     *
     * @param string $search routeKey
     *
     * @return array<int, string> filter_name or filter_name:arguments like 'role:admin,manager'
     * @phpstan-return list<string>
     */
    public function getMiddlewareForRoute(string $search, ?string $verb = null)
    {
        $options = $this->loadRoutesOptions($verb);

        if (!array_key_exists($search, $options) || !array_key_exists('middleware', $options[$search])) {
            return [];
        }

        if (is_string($options[$search]['middleware'])) {
            return [$options[$search]['middleware']];
        }

        return $options[$search]['middleware'];
    }

    /**
     * Given a
     *
     * @throws RouterException
     *
     * @deprecated Unused. Now uses buildReverseRoute().
     */
    protected function fillRouteParams(string $from, ?array $params = null)
    {
        // Find all of our back-references in the original route
        preg_match_all('/\(([^)]+)\)/', $from, $matches);

        if (empty($matches[0])) {
            return '/' . ltrim($from, '/');
        }

        /**
         * Build our resulting string, inserting the $params in
         * the appropriate places.
         *
         * @var array<int, string> $patterns
         * @phpstan-var list<string> $patterns
         */
        $patterns = $matches[0];

        foreach ($patterns as $index => $pattern) {
            if (!preg_match('#^' . $pattern . '$#u', $params[$index])) {
                throw new Exception("Parameter invalid");
            }

            // Ensure that the param we're inserting matches
            // the expected param type.
            $pos  = strpos($from, $pattern);
            $from = substr_replace($from, $params[$index], $pos, strlen($pattern));
        }

        return '/' . ltrim($from, '/');
    }

    /**
     * Does the heavy lifting of creating an actual route. You must specify
     * the request method(s) that this route will work for. They can be separated
     * by a pipe character "|" if there is more than one.
     *
     * @param array|Closure|string $to
     * @return void
     */
    protected function create(string $verb, string $from, $to, ?array $options = null)
    {
        $overwrite = false;
        $prefix = $this->group === null ? '' : $this->group . '/';

        // $from = esc(strip_tags($prefix . $from));
        $from = strip_tags($prefix . $from);

        // While we want to add a route within a group of '/',
        // it doesn't work with matching, so remove them...
        if ($from !== '/')
            $from = trim($from, '/');

        // When redirecting to named route, $to is an array like `['zombies' => '\Zombies::index']`.
        if (is_array($to) && isset($to[0])) {
            $to = $this->processArrayCallableSyntax($from, $to);
        }

        $options = array_merge($this->currentOptions ?? [], $options ?? []);

        // Hostname limiting?
        if (!empty($options['hostname'])) {
            // @todo determine if there's a way to whitelist hosts?
            if (!$this->checkHostname($options['hostname'])) {
                return;
            }

            $overwrite = true;
        }
        // Limiting to subdomains?
        elseif (!empty($options['subdomain'])) {
            // If we don't match the current subdomain, then we don't need to add the route.
            if (!$this->checkSubdomains($options['subdomain'])) {
                return;
            }

            $overwrite = true;
        }

        $routeKey = $from;

        // Replace our regex pattern placeholders with the actual thing
        // so that the Router doesn't need to know about any of this.
        foreach ($this->placeholders as $tag => $pattern) {
            $routeKey = str_ireplace(':' . $tag, $pattern, $routeKey);
        }

        // If is redirect, No processing
        if (!isset($options['redirect']) && is_string($to)) {
            // If no namespace found, add the default namespace
            if (strpos($to, '\\') === false || strpos($to, '\\') > 0) {
                $namespace = $options['namespace'] ?? $this->defaultNamespace;
                $to = trim($namespace, '\\') . '\\' . $to;
            }
            // Always ensure that we escape our namespace so we're not pointing to
            // \CodeIgniter\Routes\Controller::method.
            $to = '\\' . ltrim($to, '\\');
        }

        $name = $options['as'] ?? $routeKey;

        // Don't overwrite any existing 'froms' so that auto-discovered routes
        // do not overwrite any app/Config/Routes settings. The app
        // routes should always be the "source of truth".
        // this works only because discovered routes are added just prior
        // to attempting to route the request.
        $routeKeyExists = isset($this->routes[$verb][$routeKey]);
        if ((isset($this->routesNames[$verb][$name]) || $routeKeyExists) && !$overwrite) {
            return;
        }

        $this->routes[$verb][$routeKey] = [
            'name' => $name,
            'handler' => $to,
            'from' => $from,
        ];
        $this->routesOptions[$verb][$routeKey] = $options;
        $this->routesNames[$verb][$name] = $routeKey;

        // Is this a redirect?
        if (isset($options['redirect']) && is_numeric($options['redirect'])) {
            $this->routes['*'][$routeKey]['redirect'] = $options['redirect'];
        }
    }

    /**
     * Compares the hostname passed in against the current hostname
     * on this page request.
     *
     * @param string $hostname Hostname in route options
     */
    private function checkHostname($hostname)
    {
        // CLI calls can't be on hostname.
        if (!isset($this->httpHost)) {
            return false;
        }

        return strtolower($this->httpHost) === strtolower($hostname);
    }

    private function processArrayCallableSyntax(string $from, array $to)
    {
        // [classname, method]
        // eg, [Home::class, 'index']
        if (is_callable($to, true, $callableName)) {
            // If the route has placeholders, add params automatically.
            $params = $this->getMethodParams($from);

            return '\\' . $callableName . $params;
        }

        // [[classname, method], params]
        // eg, [[Home::class, 'index'], '$1/$2']
        if (
            isset($to[0], $to[1])
            && is_callable($to[0], true, $callableName)
            && is_string($to[1])
        ) {
            $to = '\\' . $callableName . '/' . $to[1];
        }

        return $to;
    }

    /**
     * Returns the method param string like `/$1/$2` for placeholders
     */
    private function getMethodParams(string $from)
    {
        preg_match_all('/\(.+?\)/', $from, $matches);
        $count = is_countable($matches[0]) ? count($matches[0]) : 0;

        $params = '';

        for ($i = 1; $i <= $count; $i++) {
            $params .= '/$' . $i;
        }

        return $params;
    }

    /**
     * Compares the subdomain(s) passed in against the current subdomain on this page request.
     * @param string|string[] $subdomains
     */
    private function checkSubdomains($subdomains)
    {
        // CLI calls can't be on subdomain.
        if (!isset($this->httpHost)) {
            return false;
        }

        if ($this->currentSubdomain === null) {
            $this->currentSubdomain = $this->determineCurrentSubdomain();
        }

        if (!is_array($subdomains)) {
            $subdomains = [$subdomains];
        }

        // Routes can be limited to any sub-domain. In that case, though,
        // it does require a sub-domain to be present.
        if (!empty($this->currentSubdomain) && in_array('*', $subdomains, true)) {
            return true;
        }

        return in_array($this->currentSubdomain, $subdomains, true);
    }

    /**
     * Examines the HTTP_HOST to get the best match for the subdomain. It
     * won't be perfect, but should work for our needs.
     *
     * It's especially not perfect since it's possible to register a domain
     * with a period (.) as part of the domain name.
     *
     * @return false|string the subdomain
     */
    private function determineCurrentSubdomain()
    {
        // We have to ensure that a scheme exists
        // on the URL else parse_url will mis-interpret
        // 'host' as the 'path'.
        $url = $this->httpHost;
        if (strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }

        $parsedUrl = parse_url($url);

        $host = explode('.', $parsedUrl['host']);

        if ($host[0] === 'www') {
            unset($host[0]);
        }

        // Get rid of any domains, which will be the last
        unset($host[count($host) - 1]);

        // Account for .co.uk, .co.nz, etc. domains
        if (end($host) === 'co') {
            $host = array_slice($host, 0, -1);
        }

        // If we only have 1 part left, then we don't have a sub-domain.
        if (count($host) === 1) {
            // Set it to false so we don't make it back here again.
            return false;
        }

        return array_shift($host);
    }

    /**
     * Load routes options based on verb
     *
     * @return array<string, array<string, array|int|string>> [routeKey(or from) => [key => value]]
     * @phpstan-return array<
     *     string,
     *     array{
     *         filter?: string|list<string>, namespace?: string, hostname?: string,
     *         subdomain?: string, offset?: int, priority?: int, as?: string,
     *         redirect?: int
     *     }
     * >
     */
    protected function loadRoutesOptions(?string $verb = null)
    {
        $verb ??= $this->getHTTPVerb();

        $options = $this->routesOptions[$verb] ?? [];

        if (isset($this->routesOptions['*'])) {
            foreach ($this->routesOptions['*'] as $key => $val) {
                if (isset($options[$key])) {
                    $extraOptions  = array_diff_key($val, $options[$key]);
                    $options[$key] = array_merge($options[$key], $extraOptions);
                } else {
                    $options[$key] = $val;
                }
            }
        }

        return $options;
    }

    /**
     * Get all controllers in Route Handlers
     *
     * @param string|null $verb HTTP verb. `'*'` returns all controllers in any verb.
     *
     * @return array<int, string> controller name list
     * @phpstan-return list<string>
     */
    public function getRegisteredControllers(?string $verb = '*')
    {
        $controllers = [];

        if ($verb === '*') {
            foreach ($this->defaultHTTPMethods as $tmpVerb) {
                foreach ($this->routes[$tmpVerb] as $route) {
                    $controller = $this->getControllerName($route['handler']);
                    if ($controller !== null) {
                        $controllers[] = $controller;
                    }
                }
            }
        } else {
            $routes = $this->getRoutes($verb);

            foreach ($routes as $handler) {
                $controller = $this->getControllerName($handler);
                if ($controller !== null) {
                    $controllers[] = $controller;
                }
            }
        }

        return array_unique($controllers);
    }

    /**
     * @param Closure|string $handler Handler
     * @return string|null Controller classname
     */
    private function getControllerName($handler)
    {
        if (!is_string($handler)) {
            return null;
        }

        [$controller] = explode('::', $handler, 2);

        return $controller;
    }
}
