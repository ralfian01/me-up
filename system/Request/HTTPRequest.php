<?php

namespace MVCME\Request;

use MVCME\Message\Message;
use MVCME\URI\URI;
use MVCME\URI\SiteURI;
use MVCME\Files\FileCollection;
use MVCME\Files\UploadedFile;
use InvalidArgumentException;

class HTTPRequest extends Message implements HTTPRequestInterface
{
    use RequestTrait;

    /**
     * The request method
     * @var string
     */
    protected $method;

    /**
     * The URI for this request
     * @var URI
     */
    protected $uri;

    /**
     * The detected URI path (relative to the baseURL)
     * @var string|null
     */
    protected $path;

    /**
     * File collection
     * @var FileCollection|null
     */
    protected $files;

    /**
     * The request body
     * @var string|null
     */
    protected $body;

    /**
     * Constructor
     */
    public function __construct(URI $uri, string|null $body = 'php://input')
    {
        $this->collectHeaders();

        if (
            $body == 'php://input'
            && !strpos($this->getHeaderLine('Content-Type'), 'multipart/form-data')
        ) {
            $body = file_get_contents('php://input');
        }

        $this->uri = $uri;
        $this->path = $uri instanceof SiteURI ? $uri->getRoutePath() : $uri->getPath();
        $this->method = $this->getServer('REQUEST_METHOD') ?? 'GET';
    }

    private function getHostFromUri(URI $uri)
    {
        $host = $uri->getHost();

        return $host . ($uri->getPort() ? ':' . $uri->getPort() : '');
    }

    private function isHostHeaderMissingOrEmpty()
    {
        if (!$this->hasHeader('Host'))
            return true;

        return $this->header('Host')->getValue() === '';
    }

    /**
     * Sets the URI path relative to baseURL
     * @param string $path URI path relative to baseURL
     * @return $this
     */
    private function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Returns the URI path relative to baseURL, running detection as necessary.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the request method
     * @param bool $method Whether to return in upper or lower case
     */
    public function getMethod(bool $method = false)
    {
        return ($method) ? strtoupper($this->method) : strtolower($this->method);
    }

    /**
     * Sets the request method. Used when spoofing the request
     * @return $this
     */
    public function setMethod(string $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Retrieves the URI instance
     * @return URI
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI
     * @param URI $uri New request URI to use
     * @param bool $preserveHost Preserve the original state of the Host header
     * @return static
     */
    public function setUri(URI $uri, $preserveHost = false)
    {
        $request = clone $this;
        $request->uri = $uri;

        if ($preserveHost) {
            if ($this->isHostHeaderMissingOrEmpty() && $uri->getHost() !== '') {
                $request->setHeader('Host', $this->getHostFromUri($uri));

                return $request;
            }

            if ($this->isHostHeaderMissingOrEmpty() && $uri->getHost() === '') {
                return $request;
            }

            if (!$this->isHostHeaderMissingOrEmpty()) {
                return $request;
            }
        }

        if ($uri->getHost() !== '') {
            $request->setHeader('Host', $this->getHostFromUri($uri));
        }

        return $request;
    }

    /**
     * Checks this request type.
     * @param string $type HTTP verb or 'json'
     * @return bool
     */
    public function is(string $type)
    {
        $valueUpper = strtoupper($type);

        $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'PATCH', 'OPTIONS'];

        if (in_array($valueUpper, $httpMethods, true))
            return strtoupper($this->getMethod()) === $valueUpper;


        if ($valueUpper === 'JSON')
            return strpos($this->getHeaderLine('Content-Type'), 'application/json') != false;

        throw new InvalidArgumentException('Unknown type: ' . $type);
    }

    /**
     * Attempts to detect if the current connection is secure through a few different methods.
     */
    public function isSecure()
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            return true;

        if ($this->hasHeader('X-Forwarded-Proto') && $this->header('X-Forwarded-Proto')->getValue() === 'https')
            return true;

        return $this->hasHeader('Front-End-Https')
            && !empty($this->header('Front-End-Https')->getValue())
            && strtolower($this->header('Front-End-Https')->getValue()) !== 'off';
    }

    /**
     * Grab the raw input stream and decodesthe JSON into an array
     * @param bool $assoc Whether to return objects as associative arrays
     * @param int $depth How many levels deep to decode
     * @param int $options Bitmask of options
     * @return array|bool|float|int|stdClass|null
     */
    public function getJSON(bool $assoc = false, int $depth = 512, int $options = 0)
    {
        return json_decode($this->body ?? '', $assoc, $depth, $options);
    }

    /**
     * Grab the raw input stream(send method in PUT, PATCH, DELETE) and decodes the String into an array
     * @return array
     */
    public function getRawInput()
    {
        parse_str($this->body ?? '', $output);
        return $output;
    }

    /**
     * Fetch an item from GET data
     * @param array|string|null $index Index for item to fetch from $_GET.
     * @param int|null $filter A filter name to apply.
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getGet($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('get', $index, $filter, $flags);
    }

    /**
     * Fetch an item from POST
     * @param array|string|null $index  Index for item to fetch from $_POST.
     * @param int|null $filter A filter name to apply
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getPost($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('post', $index, $filter, $flags);
    }

    /**
     * Fetch an item from POST data with fallback to GET
     * @param array|string|null $index Index for item to fetch from $_POST or $_GET
     * @param int|null $filter A filter name to apply
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getPostGet($index = null, $filter = null, $flags = null)
    {
        if ($index === null)
            return array_merge(
                $this->getGet($index, $filter, $flags),
                $this->getPost($index, $filter, $flags)
            );

        // Get the item from $_POST first then $_GET if it doesn't exist
        return isset($_POST[$index])
            ? $this->getPost($index, $filter, $flags)
            : (isset($_GET[$index])
                ? $this->getGet($index, $filter, $flags)
                : $this->getPost($index, $filter, $flags));
    }

    /**
     * Fetch an item from GET data with fallback to POST
     * @param array|string|null $index Index for item to be fetched from $_GET or $_POST
     * @param int|null $filter A filter name to apply
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getGetPost($index = null, $filter = null, $flags = null)
    {
        if ($index === null)
            return array_merge(
                $this->getPost($index, $filter, $flags),
                $this->getGet($index, $filter, $flags)
            );

        // // Get the item from $_GET first then $-POST if it doesn't exist
        return isset($_GET[$index])
            ? $this->getGet($index, $filter, $flags)
            : (isset($_POST[$index])
                ? $this->getPost($index, $filter, $flags)
                : $this->getGet($index, $filter, $flags));
    }

    /**
     * Fetch an item from the COOKIE array
     * @param array|string|null $index Index for item to be fetched from $_COOKIE
     * @param int|null $filter A filter name to be applied
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getCookie($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('cookie', $index, $filter, $flags);
    }

    /**
     * Returns an array of all files that have been uploaded with this request
     * @return array
     */
    public function getFiles()
    {
        if ($this->files === null)
            $this->files = new FileCollection();

        // return all files
        return $this->files->getFiles();
    }

    /**
     * Retrieves a single file by the name of the input field used to upload
     * @return UploadedFile|null
     */
    public function getFile(string $fileID)
    {
        if ($this->files === null)
            $this->files = new FileCollection();

        return $this->files->getFile($fileID);
    }
}
