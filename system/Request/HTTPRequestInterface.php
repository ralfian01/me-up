<?php

namespace MVCME\Request;

use MVCME\URI\URI;

interface HTTPRequestInterface
{

    /**
     * Fetch an item from the $_SERVER array
     * @param array|string|null $index Index for item to be fetched from $_SERVER
     * @param int|null $filter A filter name to be applied
     * @param array|int|null $flags
     * @return mixed
     */
    public function getServer($index = null, $filter = null, $flags = null);

    /**
     * Fetch an item from the $_ENV array
     * @param array|string|null $index Index for item to be fetched from $_ENV
     * @param int|null $filter A filter name to be applied
     * @param array|int|null $flags
     * @return mixed
     */
    public function getEnv($index = null, $filter = null, $flags = null);

    /**
     * Allows manually setting the value of PHP global, like $_GET, $_POST, etc
     * @param mixed $value
     * @return $this
     */
    public function setGlobal(string $method, $value);

    /**
     * Fetches one or more items from a global, like cookies, get, post, etc.
     * Can optionally filter the input when you retrieve it by passing in
     * a filter.
     *
     * http://php.net/manual/en/filter.filters.sanitize.php
     *
     * @param string $method Input filter constant
     * @param array|string|null $index
     * @param int|null $filter Filter constant
     * @param array|int|null $flags Options
     * @return array|bool|float|int|object|string|null
     */
    public function fetchGlobal(string $method, $index = null, ?int $filter = null, $flags = null);

    /**
     * Returns the URI path relative to baseURL, running detection as necessary.
     */
    public function getPath();

    /**
     * Get the request method
     * @param bool $method Whether to return in upper or lower case
     */
    public function getMethod(bool $method = false);

    /**
     * Sets the request method. Used when spoofing the request
     * @return $this
     */
    public function setMethod(string $method);

    /**
     * Retrieves the URI instance
     * @return URI
     */
    public function getUri();

    /**
     * Returns an instance with the provided URI
     * @param URI $uri New request URI to use
     * @param bool $preserveHost Preserve the original state of the Host header
     * @return static
     */
    public function setUri(URI $uri, $preserveHost = false);

    /**
     * Checks this request type.
     * @param string $type HTTP verb or 'json'
     * @return bool
     */
    public function is(string $type);

    /**
     * Attempts to detect if the current connection is secure through a few different methods.
     */
    public function isSecure();

    /**
     * Grab the raw input stream and decodesthe JSON into an array
     * @param bool $assoc Whether to return objects as associative arrays
     * @param int $depth How many levels deep to decode
     * @param int $options Bitmask of options
     * @return array|bool|float|int|stdClass|null
     */
    public function getJSON(bool $assoc = false, int $depth = 512, int $options = 0);

    /**
     * Grab the raw input stream(send method in PUT, PATCH, DELETE) and decodes the String into an array
     * @return array
     */
    public function getRawInput();

    /**
     * Fetch an item from GET data
     * @param array|string|null $index Index for item to fetch from $_GET.
     * @param int|null $filter A filter name to apply.
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getGet($index = null, $filter = null, $flags = null);

    /**
     * Fetch an item from POST
     * @param array|string|null $index  Index for item to fetch from $_POST.
     * @param int|null $filter A filter name to apply
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getPost($index = null, $filter = null, $flags = null);

    /**
     * Fetch an item from POST data with fallback to GET
     * @param array|string|null $index Index for item to fetch from $_POST or $_GET
     * @param int|null $filter A filter name to apply
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getPostGet($index = null, $filter = null, $flags = null);

    /**
     * Fetch an item from GET data with fallback to POST
     * @param array|string|null $index Index for item to be fetched from $_GET or $_POST
     * @param int|null $filter A filter name to apply
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getGetPost($index = null, $filter = null, $flags = null);

    /**
     * Fetch an item from the COOKIE array
     * @param array|string|null $index Index for item to be fetched from $_COOKIE
     * @param int|null $filter A filter name to be applied
     * @param array|int|null $flags
     * @return array|bool|float|int|object|string|null
     */
    public function getCookie($index = null, $filter = null, $flags = null);

    /**
     * Returns an array of all files that have been uploaded with this request
     * @return array
     */
    public function getFiles();

    /**
     * Retrieves a single file by the name of the input field used to upload
     * @return UploadedFile|null
     */
    public function getFile(string $fileID);
}
