<?php

namespace MVCME\URI;

use MVCME\Config\App;
use AppConfig\App as AppConfig;
use UnexpectedValueException;

/**
 * URI for the application site
 */
class SiteURI extends URI
{
    /**
     * The current baseURL.
     */
    private URI $baseURL;

    /**
     * The path part of baseURL.
     *
     * The baseURL "http://example.com/" → '/'
     * The baseURL "http://localhost:8888/ci431/public/" → '/ci431/public/'
     */
    private string $basePathWithoutIndexPage;

    /**
     * The Index File.
     */
    private string $indexPage;

    /**
     * List of URI segments in baseURL and indexPage.
     *
     * If the URI is "http://localhost:8888/ci431/public/index.php/test?a=b",
     * and the baseURL is "http://localhost:8888/ci431/public/", then:
     *   $baseSegments = [
     *       0 => 'ci431',
     *       1 => 'public',
     *       2 => 'index.php',
     *   ];
     */
    private array $baseSegments;

    /**
     * List of URI segments after indexPage.
     *
     * The word "URI Segments" originally means only the URI path part relative
     * to the baseURL.
     *
     * If the URI is "http://localhost:8888/ci431/public/index.php/test?a=b",
     * and the baseURL is "http://localhost:8888/ci431/public/", then:
     *   $segments = [
     *       0 => 'test',
     *   ];
     *
     * @var array
     */
    protected $segments;

    /**
     * URI path relative to baseURL.
     *
     * If the baseURL contains sub folders, this value will be different from
     * the current URI path.
     *
     * This value never starts with '/'.
     */
    private string $routePath;

    /**
     * @param string $relativePath URI path relative to baseURL. May include queries or fragments.
     * @param string|null $host Optional current hostname.
     * @param string|null $scheme Optional scheme. 'http' or 'https'.
     */
    public function __construct(App $configApp, string $relativePath = '', ?string $host = null, ?string $scheme = null)
    {
        $this->indexPage = $configApp->indexPage;

        $this->baseURL = $this->determineBaseURL($configApp, $host, $scheme);

        $this->setBasePath();

        // Fix routePath, query, fragment
        [$routePath, $query, $fragment] = $this->parseRelativePath($relativePath);

        // Fix indexPage and routePath
        $indexPageRoutePath = $this->getIndexPageRoutePath($routePath);

        // Fix the current URI
        $uri = $this->baseURL . $indexPageRoutePath;

        // applyParts
        $parts = parse_url($uri);
        if ($parts === false)
            throw new UnexpectedValueException("Cannot parse \$url");

        $parts['query'] = $query;
        $parts['fragment'] = $fragment;
        $this->applyParts($parts);

        $this->setRoutePath($routePath);
    }

    private function parseRelativePath(string $relativePath)
    {
        $parts = parse_url('http://dummy/' . $relativePath);
        if ($parts === false)
            throw new UnexpectedValueException("Cannot parse \$url");

        $routePath = $relativePath === '/' ? '/' : ltrim($parts['path'], '/');

        $query = $parts['query'] ?? '';
        $fragment = $parts['fragment'] ?? '';

        return [$routePath, $query, $fragment];
    }


    /**
     * @return URI
     */
    private function determineBaseURL(App $configApp, ?string $host, ?string $scheme)
    {
        $baseURL = $this->normalizeBaseURL($configApp);

        $uri = new URI($baseURL);

        // Update scheme
        if ($scheme !== null && $scheme !== '') {
            $uri->setScheme($scheme);
        } elseif ($configApp->secureRequest) {
            $uri->setScheme('https');
        }

        // Update host
        if ($host !== null) {
            $uri->setHost($host);
        }

        return $uri;
    }

    private function getIndexPageRoutePath(string $routePath)
    {
        // Remove starting slash unless it is `/`.
        if ($routePath !== '' && $routePath[0] === '/' && $routePath !== '/') {
            $routePath = ltrim($routePath, '/');
        }

        // Check for an index page
        $indexPage = '';
        if ($this->indexPage !== '') {
            $indexPage = $this->indexPage;

            // Check if we need a separator
            if ($routePath !== '' && $routePath[0] !== '/' && $routePath[0] !== '?') {
                $indexPage .= '/';
            }
        }

        $indexPageRoutePath = $indexPage . $routePath;

        if ($indexPageRoutePath === '/') {
            $indexPageRoutePath = '';
        }

        return $indexPageRoutePath;
    }

    private function normalizeBaseURL(App $configApp)
    {
        // It's possible the user forgot a trailing slash on their
        // baseURL, so let's help them out.
        $baseURL = rtrim($configApp->baseURL, '/ ') . '/';

        // Validate baseURL
        if (filter_var($baseURL, FILTER_VALIDATE_URL) === false)
            throw new UnexpectedValueException("'Config\App::\$baseURL is invalid.'");


        return $baseURL;
    }

    /**
     * Sets basePathWithoutIndexPage and baseSegments.
     */
    private function setBasePath(): void
    {
        $this->basePathWithoutIndexPage = $this->baseURL->getPath();

        $this->baseSegments = $this->convertToSegments($this->basePathWithoutIndexPage);

        if ($this->indexPage !== '') {
            $this->baseSegments[] = $this->indexPage;
        }
    }

    /**
     * Returns the baseURL
     * @interal
     */
    public function getBaseURL()
    {
        return (string) $this->baseURL;
    }

    /**
     * Returns the URI path relative to baseURL.
     *
     * @return string The Route path.
     */
    public function getRoutePath()
    {
        return $this->routePath;
    }

    /**
     * Formats the URI as a string.
     */
    public function __toString()
    {
        return static::createURIString(
            $this->getScheme(),
            $this->getAuthority(),
            $this->getPath(),
            $this->getQuery(),
            $this->getFragment()
        );
    }

    /**
     * Sets the route path (and segments).
     * @return self
     */
    public function setPath(string $path)
    {
        $this->setRoutePath($path);

        return $this;
    }

    /**
     * Sets the route path (and segments).
     */
    private function setRoutePath(string $routePath)
    {
        $routePath = $this->filterPath($routePath);

        $indexPageRoutePath = $this->getIndexPageRoutePath($routePath);

        $this->path = $this->basePathWithoutIndexPage . $indexPageRoutePath;

        $this->routePath = ltrim($routePath, '/');

        $this->segments = $this->convertToSegments($this->routePath);
    }

    /**
     * Converts path to segments
     */
    private function convertToSegments(string $path)
    {
        $tempPath = trim($path, '/');

        return ($tempPath === '') ? [] : explode('/', $tempPath);
    }

    /**
     * Sets the path portion of the URI based on segments.
     * @return self
     */
    private function refreshPath()
    {
        $allSegments = array_merge($this->baseSegments, $this->segments);
        $this->path  = '/' . $this->filterPath(implode('/', $allSegments));

        if ($this->routePath === '/' && $this->path !== '/') {
            $this->path .= '/';
        }

        $this->routePath = $this->filterPath(implode('/', $this->segments));

        return $this;
    }

    /**
     * Saves our parts from a parse_url() call.
     */
    protected function applyParts(array $parts)
    {
        if (!empty($parts['host'])) {
            $this->host = $parts['host'];
        }
        if (!empty($parts['user'])) {
            $this->user = $parts['user'];
        }
        if (isset($parts['path']) && $parts['path'] !== '') {
            $this->path = $this->filterPath($parts['path']);
        }
        if (!empty($parts['query'])) {
            $this->setQuery($parts['query']);
        }
        if (!empty($parts['fragment'])) {
            $this->fragment = $parts['fragment'];
        }

        // Scheme
        if (isset($parts['scheme'])) {
            $this->setScheme(rtrim($parts['scheme'], ':/'));
        } else {
            $this->setScheme('http');
        }

        // Port
        if (isset($parts['port']) && $parts['port'] !== null) {
            // Valid port numbers are enforced by earlier parse_url() or setPort()
            $this->port = $parts['port'];
        }

        if (isset($parts['pass'])) {
            $this->password = $parts['pass'];
        }
    }

    /**
     * For base_url() helper
     * @param array|string $relativePath URI string or array of URI segments
     * @param string|null  $scheme       URI scheme. E.g., http, ftp
     */
    public function baseUrl($relativePath = '', ?string $scheme = null)
    {
        $relativePath = $this->stringifyRelativePath($relativePath);

        $config = new AppConfig;
        $config->indexPage = '';

        $host = $this->getHost();

        $uri = new self($config, $relativePath, $host, $scheme);

        // Support protocol-relative links
        if ($scheme === '') {
            return substr((string) $uri, strlen($uri->getScheme()) + 1);
        }

        return (string) $uri;
    }

    /**
     * @param array|string $relativePath URI string or array of URI segments
     */
    private function stringifyRelativePath($relativePath)
    {
        if (is_array($relativePath)) {
            $relativePath = implode('/', $relativePath);
        }

        return $relativePath;
    }

    /**
     * For site_url() helper
     * @param array|string $relativePath URI string or array of URI segments
     * @param string|null  $scheme       URI scheme. E.g., http, ftp
     * @param App|null     $config       Alternate configuration to use
     */
    public function siteUrl($relativePath = '', ?string $scheme = null, ?App $config = null)
    {
        $relativePath = $this->stringifyRelativePath($relativePath);

        // Check current host.
        $host = $config === null ? $this->getHost() : null;

        $config ??= new AppConfig;

        $uri = new self($config, $relativePath, $host, $scheme);

        // Support protocol-relative links
        if ($scheme === '') {
            return substr((string) $uri, strlen($uri->getScheme()) + 1);
        }

        return (string) $uri;
    }
}
