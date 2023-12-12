<?php

namespace MVCME\URI;

use MVCME\Config\App;
use MVCME\GlobalConstants;
use UnexpectedValueException;

/**
 * Creates SiteURI using GlobalConstants.
 * This class also updates globalconstants $_SERVER and $_GET
 */
final class URIBuilder
{
    private App $appConfig;
    private GlobalConstants $globalConstants;

    public function __construct(App $appConfig, GlobalConstants $GlobalConstants)
    {
        $this->appConfig    = $appConfig;
        $this->globalConstants = $GlobalConstants;
    }

    /**
     * Create the current URI object from GlobalConstants.
     * This method updates globalconstants $_SERVER and $_GET.
     * @return SiteURI
     */
    public function createFromGlobals()
    {
        $routePath = $this->detectRoutePath();

        return $this->createURIFromRoutePath($routePath);
    }

    /**
     * Detects the current URI path relative to baseURL
     * @param string $protocol URIProtocol
     * @return string The route path
     * @internal Used for testing purposes only
     */
    public function detectRoutePath(string $protocol = '')
    {
        if ($protocol === '') {
            $protocol = $this->appConfig->uriProtocol;
        }

        switch ($protocol) {
            case 'REQUEST_URI':
                $routePath = $this->parseRequestURI();
                break;

            case 'QUERY_STRING':
                $routePath = $this->parseQueryString();
                break;

            case 'PATH_INFO':
            default:
                $routePath = $this->globalConstants->getServer($protocol) ?? $this->parseRequestURI();
                break;
        }

        return ($routePath === '/' || $routePath === '') ? '/' : ltrim($routePath, '/');
    }

    /**
     * Will parse the REQUEST_URI and automatically detect the URI from it.
     * This method updates globalconstants $_SERVER and $_GET.
     * @return string The route path (before normalization).
     */
    private function parseRequestURI()
    {
        if (
            $this->globalConstants->getServer('REQUEST_URI') === null
            || $this->globalConstants->getServer('SCRIPT_NAME') === null
        ) {
            return '';
        }

        // parse_url() returns false if no host is present, but the path or query
        // string contains a colon followed by a number. So we attach a dummy
        // host since REQUEST_URI does not include the host. This allows us to
        // parse out the query string and path.
        $parts = parse_url('http://dummy' . $this->globalConstants->getServer('REQUEST_URI'));
        $query = $parts['query'] ?? '';
        $path  = $parts['path'] ?? '';

        // Strip the SCRIPT_NAME path from the URI
        if (
            $path !== '' && $this->globalConstants->getServer('SCRIPT_NAME') !== ''
            && pathinfo($this->globalConstants->getServer('SCRIPT_NAME'), PATHINFO_EXTENSION) === 'php'
        ) {
            // Compare each segment, dropping them until there is no match
            $segments = $keep = explode('/', $path);

            foreach (explode('/', $this->globalConstants->getServer('SCRIPT_NAME')) as $i => $segment) {
                // If these segments are not the same then we're done
                if (!isset($segments[$i]) || $segment !== $segments[$i]) {
                    break;
                }

                array_shift($keep);
            }

            $path = implode('/', $keep);
        }

        // This section ensures that even on servers that require the URI to
        // contain the query string (Nginx) a correct URI is found, and also
        // fixes the QUERY_STRING Server var and $_GET array.
        if (trim($path, '/') === '' && strncmp($query, '/', 1) === 0) {
            $parts    = explode('?', $query, 2);
            $path     = $parts[0];
            $newQuery = $query[1] ?? '';

            $this->globalConstants->setServer('QUERY_STRING', $newQuery);
        } else {
            $this->globalConstants->setServer('QUERY_STRING', $query);
        }

        // Update our global GET for values likely to have been changed
        parse_str($this->globalConstants->getServer('QUERY_STRING'), $get);
        $this->globalConstants->setGetArray($get);

        return URI::removeDotSegments($path);
    }

    /**
     * Will parse QUERY_STRING and automatically detect the URI from it
     * This method updates globalconstants $_SERVER and $_GET
     * @return string The route path (before normalization).
     */
    private function parseQueryString()
    {
        $query = $this->globalConstants->getServer('QUERY_STRING') ?? (string) getenv('QUERY_STRING');

        if (trim($query, '/') === '') {
            return '/';
        }

        if (strncmp($query, '/', 1) === 0) {
            $parts    = explode('?', $query, 2);
            $path     = $parts[0];
            $newQuery = $parts[1] ?? '';

            $this->globalConstants->setServer('QUERY_STRING', $newQuery);
        } else {
            $path = $query;
        }

        // Update our global GET for values likely to have been changed
        parse_str($this->globalConstants->getServer('QUERY_STRING'), $get);
        $this->globalConstants->setGetArray($get);

        return URI::removeDotSegments($path);
    }

    /**
     * Create current URI object
     * @param string $routePath URI path relative to baseURL
     * @return SiteURI
     */
    private function createURIFromRoutePath(string $routePath)
    {
        $query = $this->globalConstants->getServer('QUERY_STRING') ?? '';

        $relativePath = $query !== '' ? $routePath . '?' . $query : $routePath;

        return new SiteURI($this->appConfig, $relativePath, $this->getHost());
    }

    /**
     * @return string|null The current hostname. Returns null if no valid host.
     */
    private function getHost()
    {
        $httpHostPort = $this->globalConstants->getServer('HTTP_HOST') ?? null;

        if ($httpHostPort !== null) {
            [$httpHost] = explode(':', $httpHostPort, 2);

            return $this->getValidHost($httpHost);
        }

        return null;
    }

    /**
     * @return string|null The valid hostname. Returns null if not valid.
     */
    private function getValidHost(string $host)
    {
        if (in_array($host, $this->appConfig->allowedHostnames, true)) {
            return $host;
        }

        return null;
    }
}
