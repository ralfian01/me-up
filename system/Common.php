<?php

use MVCME\Cookie\Cookie;
use MVCME\Cookie\CookieStore;
use MVCME\Cookie\Exceptions\CookieException;
use MVCME\Files\Exceptions\FileNotFoundException;
use MVCME\HTTP\Exceptions\HTTPException;
use MVCME\HTTP\Exceptions\RedirectException;
use MVCME\HTTP\IncomingRequest;
use MVCME\HTTP\RedirectResponse;
use MVCME\Request\HTTPRequestInterface;
use MVCME\Response\HTTPResponseInterface;
use MVCME\URI\URI;
use Config\App;
use MVCME\Service\Services;
use Config\View;
use MVCME\Request\HTTPRequest;

// Services Convenience Functions

if (!function_exists('app_timezone')) {
    /**
     * Returns the timezone the application has been set to display
     * dates in. This might be different than the timezone set
     * at the server level, as you often want to stores dates in UTC
     * and convert them on the fly for the user.
     */
    function app_timezone(): string
    {
        $config = config(App::class);

        return $config->appTimezone;
    }
}

if (!function_exists('clean_path')) {
    /**
     * A convenience method to clean paths for
     * a nicer looking output. Useful for exception
     * handling, error logging, etc.
     */
    function clean_path(string $path): string
    {
        // Resolve relative paths
        try {
            $path = realpath($path) ?: $path;
        } catch (ErrorException | ValueError $e) {
            $path = 'error file path: ' . urlencode($path);
        }

        switch (true) {
            case strpos($path, APPPATH) === 0:
                return 'APPPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(APPPATH));

            case strpos($path, SYSTEMPATH) === 0:
                return 'SYSTEMPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(SYSTEMPATH));

            case strpos($path, FCPATH) === 0:
                return 'FCPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(FCPATH));

            case defined('VENDORPATH') && strpos($path, VENDORPATH) === 0:
                return 'VENDORPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(VENDORPATH));

            case strpos($path, ROOTPATH) === 0:
                return 'ROOTPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(ROOTPATH));

            default:
                return $path;
        }
    }
}

if (!function_exists('cookie')) {
    /**
     * Simpler way to create a new Cookie instance.
     *
     * @param string $name    Name of the cookie
     * @param string $value   Value of the cookie
     * @param array  $options Array of options to be passed to the cookie
     *
     * @throws CookieException
     */
    function cookie(string $name, string $value = '', array $options = []): Cookie
    {
        return new Cookie($name, $value, $options);
    }
}

if (!function_exists('cookies')) {
    /**
     * Fetches the global `CookieStore` instance held by `Response`.
     *
     * @param Cookie[] $cookies   If `getGlobal` is false, this is passed to CookieStore's constructor
     * @param bool     $getGlobal If false, creates a new instance of CookieStore
     */
    function cookies(array $cookies = [], bool $getGlobal = true): CookieStore
    {
        if ($getGlobal) {
            return Services::response()->getCookieStore();
        }

        return new CookieStore($cookies);
    }
}

if (!function_exists('env')) {
    /**
     * Allows user to retrieve values from the environment
     * variables that have been set. Especially useful for
     * retrieving values set from the .env file for
     * use in config files.
     *
     * @param string|null $default
     *
     * @return bool|string|null
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        // Not found? Return the default value
        if ($value === false) {
            return $default;
        }

        // Handle any boolean values
        switch (strtolower($value)) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'empty':
                return '';

            case 'null':
                return null;
        }

        return $value;
    }
}

if (!function_exists('force_https')) {
    /**
     * Used to force a page to be accessed in via HTTPS.
     * Uses a standard redirect, plus will set the HSTS header
     * for modern browsers that support, which gives best
     * protection against man-in-the-middle attacks.
     *
     * @see https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security
     *
     * @param int $duration How long should the SSL header be set for? (in seconds)
     *                      Defaults to 1 year.
     *
     * @throws HTTPException
     * @throws RedirectException
     */
    function force_https(
        int $duration = 31_536_000,
        ?HTTPRequestInterface $request = null,
        ?HTTPResponseInterface $response = null
    ): void {
        $request ??= Services::request();

        if (!$request instanceof HTTPRequest) {
            return;
        }

        $response ??= Services::response();

        if ((ENVIRONMENT !== 'testing' && (is_cli() || $request->isSecure()))
            || $request->getServer('HTTPS') === 'test'
        ) {
            return;
        }

        $baseURL = config(App::class)->baseURL;

        if (strpos($baseURL, 'https://') === 0) {
            $authority = substr($baseURL, strlen('https://'));
        } elseif (strpos($baseURL, 'http://') === 0) {
            $authority = substr($baseURL, strlen('http://'));
        } else {
            $authority = $baseURL;
        }

        $uri = URI::createURIString(
            'https',
            $authority,
            $request->getUri()->getPath(), // Absolute URIs should use a "/" for an empty path
            $request->getUri()->getQuery(),
            $request->getUri()->getFragment()
        );

        // Set an HSTS header
        $response->setHeader('Strict-Transport-Security', 'max-age=' . $duration)
            ->redirect($uri)
            ->setStatusCode(307)
            ->setBody('')
            ->getCookieStore()
            ->clear();

        // throw new RedirectException($response);
    }
}

if (!function_exists('helper')) {
    /**
     * Loads a helper file into memory. Supports namespaced helpers,
     * both in and out of the 'Helpers' directory of a namespaced directory.
     *
     * Will load ALL helpers of the matching name, in the following order:
     *   1. app/Helpers
     *   2. {namespace}/Helpers
     *   3. system/Helpers
     *
     * @param array|string $filenames
     *
     * @throws FileNotFoundException
     */
    function helper($filenames): void
    {
        static $loaded = [];

        $loader = Services::locator();

        if (!is_array($filenames)) {
            $filenames = [$filenames];
        }

        // Store a list of all files to include...
        $includes = [];

        foreach ($filenames as $filename) {
            // Store our system and application helper
            // versions so that we can control the load ordering.
            $systemHelper  = null;
            $appHelper     = null;
            $localIncludes = [];

            if (strpos($filename, '_helper') === false) {
                $filename .= '_helper';
            }

            // Check if this helper has already been loaded
            if (in_array($filename, $loaded, true)) {
                continue;
            }

            // If the file is namespaced, we'll just grab that
            // file and not search for any others
            if (strpos($filename, '\\') !== false) {
                $path = $loader->locateFile($filename, 'Helpers');

                if (empty($path)) {
                    throw FileNotFoundException::forFileNotFound($filename);
                }

                $includes[] = $path;
                $loaded[]   = $filename;
            } else {
                // No namespaces, so search in all available locations
                $paths = $loader->search('Helpers/' . $filename);

                foreach ($paths as $path) {
                    if (strpos($path, APPPATH . 'Helpers' . DIRECTORY_SEPARATOR) === 0) {
                        $appHelper = $path;
                    } elseif (strpos($path, SYSTEMPATH . 'Helpers' . DIRECTORY_SEPARATOR) === 0) {
                        $systemHelper = $path;
                    } else {
                        $localIncludes[] = $path;
                        $loaded[]        = $filename;
                    }
                }

                // App-level helpers should override all others
                if (!empty($appHelper)) {
                    $includes[] = $appHelper;
                    $loaded[]   = $filename;
                }

                // All namespaced files get added in next
                $includes = [...$includes, ...$localIncludes];

                // And the system default one should be added in last.
                if (!empty($systemHelper)) {
                    $includes[] = $systemHelper;
                    $loaded[]   = $filename;
                }
            }
        }

        // Now actually include all of the files
        foreach ($includes as $path) {
            include_once $path;
        }
    }
}

if (!function_exists('lang')) {
    /**
     * A convenience method to translate a string or array of them and format
     * the result with the intl extension's MessageFormatter.
     *
     * @return string
     */
    function lang(string $line, array $args = [], ?string $locale = null)
    {
        $language = Services::language();

        // Get active locale
        $activeLocale = $language->getLocale();

        if ($locale && $locale !== $activeLocale) {
            $language->setLocale($locale);
        }

        $line = $language->getLine($line, $args);

        if ($locale && $locale !== $activeLocale) {
            // Reset to active locale
            $language->setLocale($activeLocale);
        }

        return $line;
    }
}

if (!function_exists('redirect')) {
    /**
     * Convenience method that works with the current global $request and
     * $router instances to redirect using named/reverse-routed routes
     * to determine the URL to go to.
     *
     * If more control is needed, you must use $response->redirect explicitly.
     *
     * @param string|null $route Route name or Controller::method
     */
    function redirect(?string $route = null): RedirectResponse
    {
        $response = Services::redirectresponse(null, true);

        if (!empty($route)) {
            return $response->route($route);
        }

        return $response;
    }
}

if (!function_exists('request')) {
    /**
     * Returns the shared Request.
     *
     * @return CLIRequest|IncomingRequest
     */
    function request()
    {
        return Services::request();
    }
}

if (!function_exists('response')) {
    /**
     * Returns the shared Response.
     */
    function response(): HTTPResponseInterface
    {
        return Services::response();
    }
}

if (!function_exists('route_to')) {
    /**
     * Given a route name or controller/method string and any params,
     * will attempt to build the relative URL to the
     * matching route.
     *
     * NOTE: This requires the controller/method to
     * have a route defined in the routes Config file.
     *
     * @param string     $method    Route name or Controller::method
     * @param int|string ...$params One or more parameters to be passed to the route.
     *                              The last parameter allows you to set the locale.
     *
     * @return false|string The route (URI path relative to baseURL) or false if not found.
     */
    function route_to(string $method, ...$params)
    {
        return Services::routes()->reverseRoute($method, ...$params);
    }
}

if (!function_exists('view')) {
    /**
     * Grabs the current RendererInterface-compatible class
     * and tells it to render the specified view. Simply provides
     * a convenience method that can be used in Controllers,
     * libraries, and routed closures.
     *
     * NOTE: Does not provide any escaping of the data, so that must
     * all be handled manually by the developer.
     *
     * @param array $options Options for saveData or third-party extensions.
     */
    function view(string $name, array $data = [], array $options = []): string
    {
        $renderer = Services::renderer();

        $config   = config(View::class);
        $saveData = $config->saveData;

        if (array_key_exists('saveData', $options)) {
            $saveData = (bool) $options['saveData'];
            unset($options['saveData']);
        }

        return $renderer->setData($data, 'raw')->render($name, $options, $saveData);
    }
}
