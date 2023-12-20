<?php

namespace MVCME\Message;

use MVCME\HTTP\Header;

/**
 * An HTTP message
 */
class Message implements MessageInterface
{
    /**
     * List of all HTTP request headers
     * @var array<string, Header>
     */
    protected $headers = [];

    /**
     * Holds a map of lower-case header names and their normal-case key as it is in $headers
     * @var array
     */
    protected $headerMap = [];

    /**
     * Message body
     * @var string|null
     */
    protected $body;

    // --------------------------------------------------------------------
    // Body
    // --------------------------------------------------------------------

    /**
     * Returns the Message's body
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Sets the body of the current message
     * @param string $data
     * @return $this
     */
    public function setBody($data)
    {
        $this->body = $data;

        return $this;
    }

    /**
     * Appends data to the body of the current message
     * @param string $data
     * @return $this
     */
    public function appendBody($data)
    {
        $this->body .= (string) $data;

        return $this;
    }

    // --------------------------------------------------------------------
    // Headers
    // --------------------------------------------------------------------

    /**
     * @deprecated method renamed to collectHeaders()
     */
    public function populateHeaders()
    {
        $this->collectHeaders();
    }

    /**
     * Populates the $headers array with any headers the server knows about.
     * @return void
     */
    public function collectHeaders()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? getenv('CONTENT_TYPE');
        if (!empty($contentType)) {
            $this->setHeader('Content-Type', $contentType);
        }
        unset($contentType);

        foreach (array_keys($_SERVER) as $key) {
            if (sscanf($key, 'HTTP_%s', $header) === 1) {
                // take SOME_HEADER and turn it into Some-Header
                $header = str_replace('_', ' ', strtolower($header));
                $header = str_replace(' ', '-', ucwords($header));

                $this->setHeader($header, $_SERVER[$key]);

                // Add us to the header map so we can find them case-insensitively
                $this->headerMap[strtolower($header)] = $header;
            }
        }
    }

    /**
     * Determines whether a header exists.
     */
    public function hasHeader(string $name)
    {
        $origName = $this->getHeaderName($name);

        return isset($this->headers[$origName]);
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma
     */
    public function getHeaderLine(string $name)
    {
        $origName = $this->getHeaderName($name);

        if (!array_key_exists($origName, $this->headers)) {
            return '';
        }

        return $this->headers[$origName]->getValueLine();
    }

    /**
     * Returns an array containing all Headers
     * @return array<string, Header> An array of the Header objects
     */
    public function headers()
    {
        if (empty($this->headers))
            $this->collectHeaders();

        return $this->headers;
    }

    /**
     * Returns a single Header object. If multiple headers with the same name exist, then will return an array of header objects.
     * @param string $name
     * @return array|Header|null
     */
    public function header($name)
    {
        $origName = $this->getHeaderName($name);

        return $this->headers[$origName] ?? null;
    }

    /**
     * Sets a header and it's value
     * @param array|string|null $value
     * @return $this
     */
    public function setHeader(string $name, $value)
    {
        $origName = $this->getHeaderName($name);

        if (isset($this->headers[$origName]) && is_array($this->headers[$origName]->getValue())) {
            if (!is_array($value)) {
                $value = [$value];
            }

            foreach ($value as $v) {
                $this->appendHeader($origName, $v);
            }
        } else {
            $this->headers[$origName] = new Header($origName, $value);
            $this->headerMap[strtolower($origName)] = $origName;
        }

        return $this;
    }

    /**
     * Removes a header from the list of headers we track
     * @return $this
     */
    public function removeHeader(string $name)
    {
        $origName = $this->getHeaderName($name);
        unset($this->headers[$origName], $this->headerMap[strtolower($name)]);

        return $this;
    }

    /**
     * Adds an additional header value to any headers that accept multiple values (i.e. are an array or implement ArrayAccess)
     * @return $this
     */
    public function appendHeader(string $name, ?string $value)
    {
        $origName = $this->getHeaderName($name);

        array_key_exists($origName, $this->headers)
            ? $this->headers[$origName]->appendValue($value)
            : $this->setHeader($name, $value);

        return $this;
    }

    /**
     * Adds an additional header value to any headers that accept multiple values (i.e. are an array or implement ArrayAccess)
     * @return $this
     */
    public function prependHeader(string $name, string $value)
    {
        $origName = $this->getHeaderName($name);

        $this->headers[$origName]->prependValue($value);

        return $this;
    }

    /**
     * Takes a header name in any case, and returns the normal-case version of the header.
     * @return string
     */
    protected function getHeaderName(string $name)
    {
        return $this->headerMap[strtolower($name)] ?? $name;
    }


    protected function cors()
    {
        // 
    }
}
