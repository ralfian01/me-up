<?php

namespace MVCME;

use MVCME\Config\App;
use MVCME\Service\Services;
use MVCME\Request\HTTPRequest;
use MVCME\Request\HTTPRequestInterface;
use MVCME\Response\HTTPResponse;
use MVCME\Response\HTTPResponseInterface;
use MVCME\Router\Router;
use Closure;
use Exception;
use Throwable;

/**
 * This class is the core of the framework, and will analyse the
 * request, route it to a controller, and send back the response.
 * Of course, there are variations to that flow, but this is the brains.
 */
class MVCME
{
    /**
     * Main app configuration
     * @var App
     */
    protected $config;

    /**
     * Current request
     * @var HTTPRequestInterface|null
     */
    protected $request;

    /**
     * Current response
     * @var HTTPResponseInterface
     */
    protected $response;

    /**
     * Router to use
     * @var Router
     */
    protected $router;

    /**
     * Controller to use
     * @var Closure|string
     */
    protected $controller;

    /**
     * Controller method to call
     * @var string
     */
    protected $method;

    /**
     * Output handler to use
     * @var string
     */
    protected $output;

    /**
     * Request path to use
     * @var string
     */
    protected $path;

    /**
     * Whether to enable Control Filters
     * @var bool
     */
    protected $enableMiddleware = true;

    /**
     * Constructor
     */
    public function __construct(App $config)
    {
        $this->config = $config;
    }

    /**
     * Handles some basic app and environment setup.
     * @return void
     */
    public function initialize()
    {
        // Define custom environment variables
        $this->bootstrapEnvironment();

        // Set default timezone on the server
        date_default_timezone_set($this->config->timezone ?? 'UTC');
    }

    /**
     * Load custom configurations based on the set environment.
     * If the configuration file is not available, the application cannot be run
     * @return void
     */
    protected function bootstrapEnvironment()
    {
        $bootEnvPath = CONFIGPATH . "Boot/" . ENVIRONMENT . '.php';

        if (file_exists($bootEnvPath) && is_file($bootEnvPath)) {
            require_once($bootEnvPath);
        } else {
            header('HTTP/1.1 503 Service Unavailable.', true, 503);
            echo 'The application environment is not set correctly.';
            exit(EXIT_ERROR);
        }
    }

    /**
     * Get Request object
     * @return void
     */
    protected function getRequestObject()
    {
        $this->request = Services::request();
    }

    /**
     * Get Response object
     * @return void
     */
    protected function getResponseObject()
    {
        $this->response = Services::response();

        // Assume success until proven otherwise.
        $this->response->setStatusCode(200);
    }

    /**
     * Determines the path to use for us to try to route to, based on the HTTPRequest path
     * @return string
     */
    protected function determinePath()
    {
        if (!empty($this->path))
            return $this->path;

        return method_exists($this->request, 'getPath')
            ? $this->request->getPath()
            : $this->request->getUri()->getPath();
    }

    /**
     * Sends the output of this request back to the client
     * @return void
     */
    protected function sendResponse()
    {
        $this->response->send();
    }

    /**
     * If the config value 'secureRequest' is true
     * @param int $duration How long the Strict Transport Security
     *                      should be enforced for this URL
     * @return void
     */
    protected function useSecureConnection($duration = 31_536_000)
    {
        if ($this->config->secureRequest !== true)
            return;

        // force_https($duration, $this->request, $this->response);
    }

    /**
     * @return void|string|array
     */
    protected function filterRoute()
    {
        $routes = Services::routes()->loadRoutes();

        $this->router = Services::router($routes, $this->request);

        $path = $this->determinePath();

        $this->controller = $this->router->handle($path);
        $this->method = $this->router->methodName();

        return $this->router->getMiddleware();
    }

    /**
     * Handle requests based on current URI, payload, and http header
     * @return void
     */
    protected function handleRequest()
    {
        $this->useSecureConnection();

        $filterRoute = $this->filterRoute();

        $uri = $this->determinePath();

        if ($this->enableMiddleware) {
            $middleware = Services::middleware();

            if ($filterRoute !== null) {

                $middleware->enablemiddleware($filterRoute, 'before');
                $middleware->enablemiddleware($filterRoute, 'after');
            }

            // Fire "before" middleware
            $middlewareResponse = $middleware->fire($uri, 'before');

            if ($middlewareResponse instanceof HTTPResponseInterface) {
                return $middlewareResponse;
            }

            if ($middlewareResponse instanceof HTTPRequestInterface) {
                $this->request = $middlewareResponse;
            }
        }

        // Start Closure controller
        $returned = $this->startController();

        // Closure controller has run in startController().
        if (!is_callable($this->controller)) {
            $controller = $this->createController();

            if (!method_exists($controller, '_remap') && !is_callable([$controller, $this->method], false)) {
                throw new Exception("Method {$this->method} not found in controller {$controller}");
            }

            $returned = $this->fireController($controller);
        }

        $this->setResponseOutput($returned);

        unset($uri);

        return $this->response;
    }

    protected function setResponseOutput($returned = null)
    {
        if ($returned instanceof HTTPResponseInterface) {
            $returned = $returned->getBody();
        }

        $this->response->setBody($returned);
    }

    /**
     * Instantiates the controller class
     * @return Controller
     */
    protected function createController()
    {
        assert(is_string($this->controller));

        $class = new $this->controller();
        $class->initController($this->request, $this->response);

        return $class;
    }

    /**
     * Fire up the controller, allowing for _remap methods to function
     * @param Controller $class
     * @return false|HTTPResponseInterface|string|void
     */
    protected function fireController($class)
    {
        // This is a Web request or PHP CLI request
        $params = $this->router->params();

        return $class->{$this->method}(...$params);
    }

    /**
     * Now that everything has been setup, this method attempts to run the
     * controller method and make the script go. If it's not able to, will
     * show the appropriate Page Not Found error
     * @return HTTPResponse|string|void
     */
    protected function startController()
    {
        // Is it routed to a Closure?
        if (is_object($this->controller) && (get_class($this->controller) === 'Closure')) {
            $controller = $this->controller;

            return $controller(...$this->router->params());
        }

        // No controller specified - we don't know what to do now.
        if (empty($this->controller)) {
            throw new Exception("Controller {$this->controller} is empty");
        }

        // Try to autoload the class
        if (!class_exists($this->controller, true) || $this->method[0] === '_') {
            throw new Exception("Cannot found controller {$this->controller} or {$this->method[0]}");
        }
    }

    /**
     * Fire up the application!
     *
     * The main entry point into the application.
     * Gets the required class instances, fires the filter,routes the response, and loads the controller.
     * Generally making all the parts work together.
     * @return HTTPResponse|void
     */
    public function fire()
    {
        $this->getRequestObject();
        $this->getResponseObject();

        // Start routing
        try {
            $this->response = $this->handleRequest();
        } catch (Throwable $th) {
            // Print any exception
            $trace = Services::traceError($th);

            $this->response
                ->setContentType('application/json')
                ->setStatusCode(500)
                ->setBody(json_encode($trace, JSON_PRETTY_PRINT));
        }

        $this->sendResponse();

        // try {
        //     $this->response = $this->handleRequest($routes, config(Cache::class), $returnResponse);
        // } catch (ResponsableInterface | DeprecatedRedirectException $e) {
        //     $this->outputBufferingEnd();
        //     if ($e instanceof DeprecatedRedirectException) {
        //         $e = new RedirectException($e->getMessage(), $e->getCode(), $e);
        //     }

        //     $this->response = $e->getResponse();
        // } catch (PageNotFoundException $e) {
        //     $this->response = $this->display404errors($e);
        // } catch (Throwable $e) {
        //     $this->outputBufferingEnd();

        //     throw $e;
        // }
    }

    // /**
    //  * Displays a 404 Page Not Found error. If set, will try to
    //  * call the 404Override controller/method that was set in routing config.
    //  *
    //  * @return HTTPResponse|void
    //  */
    // protected function display404errors(PageNotFoundException $e)
    // {
    //     // Is there a 404 Override available?
    //     if ($override = $this->router->get404Override()) {
    //         $returned = null;

    //         if ($override instanceof Closure) {
    //             echo $override($e->getMessage());
    //         } elseif (is_array($override)) {
    //             $this->benchmark->start('controller');
    //             $this->benchmark->start('controller_constructor');

    //             $this->controller = $override[0];
    //             $this->method     = $override[1];

    //             $controller = $this->createController();
    //             $returned   = $this->runController($controller);
    //         }

    //         unset($override);

    //         $cacheConfig = config(Cache::class);
    //         $this->gatherOutput($cacheConfig, $returned);

    //         return $this->response;
    //     }

    //     // Display 404 Errors
    //     $this->response->setStatusCode($e->getCode());

    //     $this->outputBufferingEnd();

    //     // Throws new PageNotFoundException and remove exception message on production.
    //     throw PageNotFoundException::forPageNotFound(
    //         (ENVIRONMENT !== 'production' || !$this->isWeb()) ? $e->getMessage() : null
    //     );
    // }
}
