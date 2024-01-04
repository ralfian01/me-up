<?php

namespace MVCME\Middleware;

use MVCME\Request\HTTPRequest;
use MVCME\Response\HTTPResponse;
use MVCME\Config\Middleware as middlewareConfig;
use Exception;

/**
 * Middleware
 */
class Middleware
{
    /**
     * The original config file
     * @var MiddlewareConfig
     */
    protected $config;

    /**
     * The active HTTPRequest
     * @var HTTPRequest
     */
    protected $request;

    /**
     * The active Response instance
     * @var HTTPResponse
     */
    protected $response;

    /**
     * Whether we've done initial processing
     * on the filter lists.
     *
     * @var bool
     */
    protected $initialized = false;

    /**
     * The processed middleware that will
     * be used to check against.
     *
     * @var array<string, array>
     */
    protected $middleware = [
        'before' => [],
        'after'  => [],
    ];

    /**
     * The collection of middleware' class names that will
     * be used to execute in each position.
     *
     * @var array<string, array>
     */
    protected $middlewareClass = [
        'before' => [],
        'after'  => [],
    ];

    /**
     * Any arguments to be passed to middleware.
     *
     * @var array<string, array<int, string>|null> [name => params]
     * @phpstan-var array<string, list<string>|null>
     */
    protected $arguments = [];

    /**
     * Any arguments to be passed to middlewareClass.
     *
     * @var array<string, array|null> [classname => arguments]
     * @phpstan-var array<class-string, array<string, list<string>>|null>
     */
    protected $argumentsClass = [];

    /**
     * Constructor.
     *
     * @param middlewareConfig $config
     */
    public function __construct($config, HTTPRequest $request, HTTPResponse $response)
    {
        $this->config = $config;
        $this->request = &$request;
        $this->setResponse($response);
    }

    /**
     * Set the response explicitly.
     *
     * @return void
     */
    public function setResponse(HTTPResponse $response)
    {
        $this->response = $response;
    }

    /**
     * Fire up through all of the middleware for the specified uri and position.
     * @param string $uri URI path relative to baseURL
     * @return HTTPRequest|HTTPResponse|string|null
     */
    public function fire(string $uri, string $position = 'before')
    {
        $this->initialize(strtolower($uri));

        foreach ($this->middlewareClass[$position] as $className) {
            $class = new $className();

            if (!$class instanceof MiddlewareInterface) {
                throw new Exception("Class {$class} do not implement MiddlewareInterface");
            }

            if ($position === 'before') {
                $result = $class->before(
                    $this->request,
                    $this->argumentsClass[$className] ?? null
                );

                if ($result instanceof HTTPRequest) {
                    $this->request = $result;

                    continue;
                }

                // If the response object was sent back,
                // then send it and quit.
                if ($result instanceof HTTPResponse) {
                    // short circuit - bypass any other middleware
                    return $result;
                }
                // Ignore an empty result
                if (empty($result)) {
                    continue;
                }

                return $result;
            }

            if ($position === 'after') {
                $result = $class->after(
                    $this->request,
                    $this->response,
                    $this->argumentsClass[$className] ?? null
                );

                if ($result instanceof HTTPResponse) {
                    $this->response = $result;

                    continue;
                }
            }
        }

        return $position === 'before' ? $this->request : $this->response;
    }

    /**
     * Runs through our list of middleware provided by the configuration object to get them ready for use
     * @param string|null $uri URI path relative to baseURL (all lowercase)
     * @return Middleware
     */
    public function initialize(?string $uri = null)
    {
        if ($this->initialized === true) {
            return $this;
        }

        $this->processGlobals($uri);
        $this->processMiddleware($uri);

        // Set the toolbar filter to the last position to be executed
        if (
            in_array('toolbar', $this->middleware['after'], true)
            && ($count = count($this->middleware['after'])) > 1
            && $this->middleware['after'][$count - 1] !== 'toolbar'
        ) {
            array_splice($this->middleware['after'], array_search('toolbar', $this->middleware['after'], true), 1);
            $this->middleware['after'][] = 'toolbar';
        }

        $this->processAliasesToClass('before');
        $this->processAliasesToClass('after');

        $this->initialized = true;

        return $this;
    }

    /**
     * Returns the processed middleware array.
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Returns the middlewareClass array.
     * @return array
     */
    public function getMiddlewareClass()
    {
        return $this->middlewareClass;
    }

    /**
     * Ensures that a specific filter is on and enabled for the current request.     *
     * @return $this
     */
    private function applyMiddleware(string $name, string $when = 'before')
    {
        // Get arguments and clean name
        [$name, $arguments] = $this->getCleanName($name);
        $this->arguments[$name] = ($arguments !== []) ? $arguments : null;

        if (class_exists($name)) {
            $this->config->aliases[$name] = $name;
        } elseif (!array_key_exists($name, $this->config->aliases)) {
            throw new Exception("Middleware alias {$name} does not exists");
        }

        $classNames = (array) $this->config->aliases[$name];

        foreach ($classNames as $className) {
            $this->argumentsClass[$className] = $this->arguments[$name] ?? null;
        }

        if (!isset($this->middleware[$when][$name])) {
            $this->middleware[$when][] = $name;
            $this->middlewareClass[$when] = array_merge($this->middlewareClass[$when], $classNames);
        }

        return $this;
    }

    /**
     * Get clean name and arguments
     *
     * @param string $name filter_name or filter_name:arguments like 'role:admin,manager'
     *
     * @return array [name, arguments]
     * @phpstan-return array{0: string, 1: list<string>}
     */
    private function getCleanName(string $name): array
    {
        $arguments = [];

        if (strpos($name, ':') !== false) {
            [$name, $arguments] = explode(':', $name);

            $arguments = explode(',', $arguments);
            array_walk($arguments, static function (&$item) {
                $item = trim($item);
            });
        }

        return [$name, $arguments];
    }

    /**
     * Ensures that specific middleware are on and enabled for the current request
     * @return Middleware
     */
    public function enableMiddleware(array $names, string $when = 'before')
    {
        foreach ($names as $filter) {
            $this->applyMiddleware($filter, $when);
        }

        return $this;
    }

    /**
     * Returns the arguments for a specified key, or all.
     * @return array
     */
    public function getArguments(?string $key = null)
    {
        return $key === null ? $this->arguments : $this->arguments[$key];
    }

    // --------------------------------------------------------------------
    // Processors
    // --------------------------------------------------------------------

    /**
     * Add any applicable (not excluded) global filter settings to the mix.
     *
     * @param string|null $uri URI path relative to baseURL (all lowercase)
     *
     * @return void
     */
    protected function processGlobals(?string $uri = null)
    {
        if (!isset($this->config->globals) || !is_array($this->config->globals)) {
            return;
        }

        $uri = strtolower(trim($uri ?? '', '/ '));

        // Add any global middleware, unless they are excluded for this URI
        $sets = ['before', 'after'];

        foreach ($sets as $set) {
            if (isset($this->config->globals[$set])) {
                // look at each alias in the group
                foreach ($this->config->globals[$set] as $alias => $rules) {
                    $keep = true;
                    if (is_array($rules)) {
                        // see if it should be excluded
                        if (isset($rules['except'])) {
                            // grab the exclusion rules
                            $check = $rules['except'];
                            if ($this->checkExcept($uri, $check)) {
                                $keep = false;
                            }
                        }
                    } else {
                        $alias = $rules; // simple name of filter to apply
                    }

                    if ($keep) {
                        $this->middleware[$set][] = $alias;
                    }
                }
            }
        }
    }

    /**
     * Add any applicable configured middleware to the mix.
     * @param string|null $uri URI path relative to baseURL (all lowercase)
     * @return void
     */
    protected function processMiddleware(?string $uri = null)
    {
        if (!isset($this->config->middleware) || !$this->config->middleware) {
            return;
        }

        $uri = strtolower(trim($uri, '/ '));

        // Add any middleware that apply to this URI
        foreach ($this->config->middleware as $alias => $settings) {
            // Look for inclusion rules
            if (isset($settings['before'])) {
                $path = $settings['before'];

                if ($this->pathApplies($uri, $path)) {
                    // Get arguments and clean name
                    [$name, $arguments] = $this->getCleanName($alias);

                    $this->middleware['before'][] = $name;

                    $this->registerArguments($name, $arguments);
                }
            }

            if (isset($settings['after'])) {
                $path = $settings['after'];

                if ($this->pathApplies($uri, $path)) {
                    // Get arguments and clean name
                    [$name, $arguments] = $this->getCleanName($alias);

                    $this->middleware['after'][] = $name;

                    // The arguments may have already been registered in the before filter.
                    // So disable check.
                    $this->registerArguments($name, $arguments, false);
                }
            }
        }
    }

    /**
     * @param string $name      filter alias
     * @param array  $arguments filter arguments
     * @param bool   $check     if true, check if already defined
     */
    private function registerArguments(string $name, array $arguments, bool $check = true): void
    {
        if ($arguments !== []) {
            if ($check && array_key_exists($name, $this->arguments)) {
                throw new Exception(
                    '"' . $name . '" already has arguments: '
                        . (($this->arguments[$name] === null) ? 'null' : implode(',', $this->arguments[$name]))
                );
            }

            $this->arguments[$name] = $arguments;
        }

        $classNames = (array) $this->config->aliases[$name];

        foreach ($classNames as $className) {
            $this->argumentsClass[$className] = $this->arguments[$name] ?? null;
        }
    }

    /**
     * Maps filter aliases to the equivalent filter classes
     *
     * @return void
     *
     * @throws FilterException
     */
    protected function processAliasesToClass(string $position)
    {
        foreach ($this->middleware[$position] as $alias => $rules) {
            if (is_numeric($alias) && is_string($rules)) {
                $alias = $rules;
            }

            if (!array_key_exists($alias, $this->config->aliases)) {
                throw new Exception("Middleware alias {$alias} does not exists");
            }

            if (is_array($this->config->aliases[$alias])) {
                $this->middlewareClass[$position] = array_merge($this->middlewareClass[$position], $this->config->aliases[$alias]);
            } else {
                $this->middlewareClass[$position][] = $this->config->aliases[$alias];
            }
        }

        // when using applyMiddleware() we already write the class name in $middlewareClass as well as the
        // alias in $middleware. This leads to duplicates when using route middleware.
        // Since some middleware like rate limiters rely on being executed once a request we filter em here.
        $this->middlewareClass[$position] = array_values(array_unique($this->middlewareClass[$position]));
    }

    /**
     * Check paths for match for URI
     *
     * @param string       $uri   URI to test against
     * @param array|string $paths The path patterns to test
     *
     * @return bool True if any of the paths apply to the URI
     */
    private function pathApplies(string $uri, $paths)
    {
        // empty path matches all
        if (empty($paths)) {
            return true;
        }

        // make sure the paths are iterable
        if (is_string($paths)) {
            $paths = [$paths];
        }

        return $this->checkPseudoRegex($uri, $paths);
    }

    /**
     * Check except paths
     *
     * @param string       $uri   URI path relative to baseURL (all lowercase)
     * @param array|string $paths The except path patterns
     *
     * @return bool True if the URI matches except paths.
     */
    private function checkExcept(string $uri, $paths): bool
    {
        // empty array does not match anything
        if ($paths === []) {
            return false;
        }

        // make sure the paths are iterable
        if (is_string($paths)) {
            $paths = [$paths];
        }

        return $this->checkPseudoRegex($uri, $paths);
    }

    /**
     * Check the URI path as pseudo-regex
     *
     * @param string $uri   URI path relative to baseURL (all lowercase)
     * @param array  $paths The except path patterns
     */
    private function checkPseudoRegex(string $uri, array $paths): bool
    {
        // treat each path as pseudo-regex
        foreach ($paths as $path) {
            // need to escape path separators
            $path = str_replace('/', '\/', trim($path, '/ '));
            // need to make pseudo wildcard real
            $path = strtolower(str_replace('*', '.*', $path));

            // Does this rule apply here?
            if (preg_match('#^' . $path . '$#', $uri, $match) === 1) {
                return true;
            }
        }

        return false;
    }
}
