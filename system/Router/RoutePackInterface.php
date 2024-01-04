<?php

namespace MVCME\Router;

interface RoutePackInterface
{

    /**
     * Loads main routes file and discover routes
     * @return void
     */
    public function addRoute($routeRule = null);

    /**
     * Loads main routes file and discover routes
     * @return $this
     */
    public function loadRoutes(?string $routeFile = null);

    /**
     * Registers a new constraint with the system. Constraints are used
     * by the routes as placeholders for regular expressions to make defining
     * the routes more human-friendly
     *
     * @param array|string $placeholder
     */
    public function addPlaceholder($placeholder, ?string $pattern = null);

    /**
     * For `spark routes`
     * @return array<string, string>
     */
    public function getPlaceholders();

    /**
     * Returns the 404 Override setting, which can be null, a closure or the controller/string.
     * @return Closure|string|null
     */
    public function getDefault404();

    /**
     * Sets the class/method that should be called if routing doesn't
     * find a match. It can be either a closure or the controller/method
     * name exactly like a route is defined: Users::index
     * @param callable|string|null $callable
     * @return $this
     */
    public function setDefault404($callable = null);

    /**
     * Returns the name of the default controller
     * @return string
     */
    public function getDefaultController();

    /**
     * Sets the default controller to use when no other controller has been specified
     * @return $this
     */
    public function setDefaultController(string $value);

    /**
     * Returns the name of the default method to use within the controller.
     * @return string
     */
    public function getDefaultMethod();

    /**
     * Sets the default method to call on the controller when no other method has been set in the route.
     * @return $this
     */
    public function setDefaultMethod(string $value);

    /**
     * Returns the default namespace as set in the Routes config file.
     * @return string
     */
    public function getDefaultNamespace();

    /**
     * Sets the default namespace to use for Controllers when no other namespace has been specified
     * @return $this
     */
    public function setDefaultNamespace(string $value);

    /**
     * Returns the current HTTP Verb being used.
     * @return string
     */
    public function getHTTPVerb();

    /**
     * Sets the current HTTP verb
     * @param string $verb HTTP verb
     * @return $this
     */
    public function setHTTPVerb(string $verb);

    /**
     * Returns the raw array of available routes.
     * @param bool $includeWildcard Whether to include '*' routes.
     * @return array
     */
    public function getRoutes(?string $verb = null, bool $includeWildcard = true);

    /**
     * Returns one or all routes options
     * @return array<string, int|string> [key => value]
     */
    public function getRoutesOptions(?string $from = null, ?string $verb = null);

    /**
     * Group a series of routes under a single URL segment. This is handy
     * for grouping items into an admin area, like
     *
     * @param string $name The name to group/prefix the routes with.
     * @param array|callable ...$params
     * @return void
     */
    public function group(string $name, ...$params);

    /**
     * Specifies a single route to match for multiple HTTP Verbs.
     *
     * Example:
     *  $route->match( ['get', 'post'], 'users/(:num)', 'users/$1);
     *
     * @param array|Closure|string $to
     */
    public function match(array $verbs = [], string $from = '', $to = '', ?array $options = null);

    /**
     * Specifies a route that is only available to GET requests
     * @param array|Closure|string $to
     * @return $this
     */
    public function get(string $from, $to, ?array $options = null);

    /**
     * Specifies a route that is only available to POST requests
     * @param array|Closure|string $to
     */
    public function post(string $from, $to, ?array $options = null);

    /**
     * Specifies a route that is only available to PUT requests
     * @param array|Closure|string $to
     */
    public function put(string $from, $to, ?array $options = null);

    /**
     * Specifies a route that is only available to DELETE requests
     * @param array|Closure|string $to
     */
    public function delete(string $from, $to, ?array $options = null);

    /**
     * Specifies a route that is only available to HEAD requests
     * @param array|Closure|string $to
     */
    public function head(string $from, $to, ?array $options = null);

    /**
     * Specifies a route that is only available to PATCH requests
     * @param array|Closure|string $to
     */
    public function patch(string $from, $to, ?array $options = null);

    /**
     * Specifies a route that is only available to OPTIONS requests
     * @param array|Closure|string $to
     */
    public function options(string $from, $to, ?array $options = null);

    /**
     * Checks a route (using the "from") to see if it's filtered or not.
     */
    public function isFiltered(string $search, ?string $verb = null);

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
     * @return array<int, string> filter_name or filter_name:arguments like 'role:admin,manager'
     */
    public function getMiddlewareForRoute(string $search, ?string $verb = null);

    /**
     * Get all controllers in Route Handlers
     * @param string|null $verb HTTP verb. `'*'` returns all controllers in any verb
     * @return array<int, string> controller name list
     */
    public function getRegisteredControllers(?string $verb = '*');
}
