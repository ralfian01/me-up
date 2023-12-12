<?php

namespace MVCME\Message;

/**
 * Expected behavior of an HTTP message
 */
interface MessageInterface
{
    /**
     * Returns the Message's body
     * @return string|null
     */
    public function getBody();

    /**
     * Sets the body of the current message
     * @param string $data
     * @return $this
     */
    public function setBody($data);

    /**
     * Appends data to the body of the current message
     * @param string $data
     * @return $this
     */
    public function appendBody($data);

    // --------------------------------------------------------------------
    // Headers
    // --------------------------------------------------------------------

    /**
     * @deprecated method renamed to collectHeaders()
     */
    public function populateHeaders();

    /**
     * Populates the $headers array with any headers the server knows about.
     * @return void
     */
    public function collectHeaders();

    /**
     * Determines whether a header exists
     * @return bool
     */
    public function hasHeader(string $name);

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma
     * 
     * @return string
     */
    public function getHeaderLine(string $name);

    /**
     * Returns an array containing all Headers
     * @return array<string, Header> An array of the Header objects
     */
    public function headers();

    /**
     * Returns a single Header object. If multiple headers with the same name exist, then will return an array of header objects.
     * @param string $name
     * @return array|Header|null
     */
    public function header($name);

    /**
     * Sets a header and it's value
     * @param array|string|null $value
     * @return $this
     */
    public function setHeader(string $name, $value);

    /**
     * Removes a header from the list of headers we track
     * @return $this
     */
    public function removeHeader(string $name);

    /**
     * Adds an additional header value to any headers that accept multiple values (i.e. are an array or implement ArrayAccess)
     * @return $this
     */
    public function appendHeader(string $name, ?string $value);

    /**
     * Adds an additional header value to any headers that accept multiple values (i.e. are an array or implement ArrayAccess)
     * @return $this
     */
    public function prependHeader(string $name, string $value);
}
