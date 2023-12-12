<?php

namespace MVCME\Response;

use MVCME\Cookie\CookieStore;
use MVCME\Cookie\Cookie;
use MVCME\Message\Message;
use MVCME\Service\Services;
use AppConfig\Cookie as CookieConfig;
use BadMethodCallException;
use InvalidArgumentException;
use Exception;
use OutOfRangeException;

/**
 * Representation of server-side response
 */
class HTTPResponse extends Message implements HTTPResponseInterface
{
    /**
     * HTTP status codes
     */
    protected static array $statusCodes = [
        // 1xx: Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing', // http://www.iana.org/go/rfc2518
        103 => 'Early Hints', // http://www.ietf.org/rfc/rfc8297.txt

        // 2xx: Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information', // 1.1
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status', // http://www.iana.org/go/rfc4918
        208 => 'Already Reported', // http://www.iana.org/go/rfc5842
        226 => 'IM Used', // 1.1; http://www.ietf.org/rfc/rfc3229.txt

        // 3xx: Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found', // Formerly 'Moved Temporarily'
        303 => 'See Other', // 1.1
        304 => 'Not Modified',
        305 => 'Use Proxy', // 1.1
        306 => 'Switch Proxy', // No longer used
        307 => 'Temporary Redirect', // 1.1
        308 => 'Permanent Redirect', // 1.1; Experimental; http://www.ietf.org/rfc/rfc7238.txt

        // 4xx: Client error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large', // https://www.iana.org/assignments/http-status-codes/http-status-codes.xml
        414 => 'URI Too Long', // https://www.iana.org/assignments/http-status-codes/http-status-codes.xml
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm a teapot", // April's Fools joke; http://www.ietf.org/rfc/rfc2324.txt

        // 419 (Authentication Timeout) is a non-standard status code with unknown origin
        421 => 'Misdirected Request', // http://www.iana.org/go/rfc7540 Section 9.1.2
        422 => 'Unprocessable Content', // https://www.iana.org/assignments/http-status-codes/http-status-codes.xml
        423 => 'Locked', // http://www.iana.org/go/rfc4918
        424 => 'Failed Dependency', // http://www.iana.org/go/rfc4918
        425 => 'Too Early', // https://datatracker.ietf.org/doc/draft-ietf-httpbis-replay/
        426 => 'Upgrade Required',
        428 => 'Precondition Required', // 1.1; http://www.ietf.org/rfc/rfc6585.txt
        429 => 'Too Many Requests', // 1.1; http://www.ietf.org/rfc/rfc6585.txt
        431 => 'Request Header Fields Too Large', // 1.1; http://www.ietf.org/rfc/rfc6585.txt
        451 => 'Unavailable For Legal Reasons', // http://tools.ietf.org/html/rfc7725
        499 => 'Client Closed Request', // http://lxr.nginx.org/source/src/http/ngx_http_request.h#0133

        // 5xx: Server error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates', // 1.1; http://www.ietf.org/rfc/rfc2295.txt
        507 => 'Insufficient Storage', // http://www.iana.org/go/rfc4918
        508 => 'Loop Detected', // http://www.iana.org/go/rfc5842
        510 => 'Not Extended', // http://www.ietf.org/rfc/rfc2774.txt
        511 => 'Network Authentication Required', // http://www.ietf.org/rfc/rfc6585.txt
        599 => 'Network Connect Timeout Error', // https://httpstatuses.com/599
    ];

    /**
     * The current reason phrase
     * @var string
     */
    protected $reason = '';

    /**
     * The current status code
     * @var int
     */
    protected $statusCode = 200;

    /**
     * CookieStore instance
     * @var CookieStore
     */
    protected $cookieStore;

    /**
     * Type of format the body is in.
     * Valid: html, json, xml
     * @var string
     */
    protected $bodyFormat = 'html';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Default to an HTML Content-Type
        $this->setContentType('text/html');
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.     *
     * @param int $code The 3-digit integer result code to set.
     * @param string $reason The reason phrase to use with the
     *                       provided status code; if none is provided, will
     *                       default to the IANA name
     * @return $this
     * 
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    public function setStatusCode(int $code, string $reason = '')
    {
        // Valid range?
        if ($code < 100 || $code > 599)
            throw new OutOfRangeException("Argument \$code is out of range. Valid \$code argument between 100 - 599");

        // Unknown and no message
        if (!array_key_exists($code, static::$statusCodes) && empty($reason))
            throw new InvalidArgumentException("The \$code you entered is not available or does not have a status response");

        $this->statusCode = $code;
        $this->reason = !empty($reason) ? $reason : self::$statusCodes[$code];
        return $this;
    }

    /**
     * Gets the response status code
     * @return int Status code
     */
    public function getStatusCode()
    {
        if (empty($this->statusCode))
            throw new BadMethodCallException("\$statusCode is invalid");

        return $this->statusCode;
    }

    /**
     * Sets the Content Type header for this response with the mime type and, optionally, the charset
     * @return $this
     */
    public function setContentType(string $mime, string $charset = 'UTF-8')
    {
        // add charset attribute if not already there and provided as parm
        if ((strpos($mime, 'charset=') < 1) && !empty($charset)) {
            $mime .= '; charset=' . $charset;
        }

        $this->removeHeader('Content-Type'); // replace current content type
        $this->setHeader('Content-Type', $mime);

        return $this;
    }

    /**
     * Converts the $body into JSON and sets the Content Type header
     * @param array|object|string $body
     * @return $this
     */
    public function setJSON($body, bool $unencoded = false)
    {
        $this->body = $this->formatBody($body, 'json' . ($unencoded ? '-unencoded' : ''));
        return $this;
    }

    /**
     * Returns the current body, converted to JSON is it isn't already
     * @return string|null
     * @throws InvalidArgumentException If the body property is not array.
     */
    public function getJSON()
    {
        $body = $this->body;

        if ($this->bodyFormat !== 'json')
            $body = Services::format()->getFormatter('application/json')->format($body);

        return $body ?: null;
    }

    /**
     * Converts $body into XML, and sets the correct Content-Type.
     * @param array|string $body
     * @return $this
     */
    public function setXML($body)
    {
        $this->body = $this->formatBody($body, 'xml');

        return $this;
    }

    /**
     * Retrieves the current body into XML and returns it.
     * @return bool|string|null
     * @throws InvalidArgumentException If the body property is not array.
     */
    public function getXML()
    {
        $body = $this->body;

        if ($this->bodyFormat !== 'xml')
            $body = Services::format()->getFormatter('application/xml')->format($body);

        return $body;
    }

    /**
     * Handles conversion of the data into the appropriate format, and sets the correct Content-Type header for our response.
     * @param array|object|string $body
     * @param string $format Valid: json, xml
     * @return false|string
     * @throws InvalidArgumentException If the body property is not string or array.
     */
    protected function formatBody($body, string $format)
    {
        $this->bodyFormat = ($format === 'json-unencoded' ? 'json' : $format);
        $mime = "application/{$this->bodyFormat}";
        $this->setContentType($mime);

        // Nothing much to do for a string...
        if (!is_string($body) || $format === 'json-unencoded')
            $body = Services::format()->getFormatter($mime)->format($body);

        return $body;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     * @return string Reason phrase; must return an empty string if none present

     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    public function getReasonPhrase()
    {
        if ($this->reason === '')
            return !empty($this->statusCode) ? static::$statusCodes[$this->statusCode] : '';

        return $this->reason;
    }


    // --------------------------------------------------------------------
    // Output Methods
    // --------------------------------------------------------------------

    /**
     * Sends the output to the browser
     * @return $this
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendBody();

        return $this;
    }

    /**
     * Sends the headers of this HTTP response to the browser
     * @return $this
     */
    public function sendHeaders()
    {
        // HTTP Status
        header(sprintf(
            'HTTP/%s %s',
            $this->getStatusCode(),
            $this->getReasonPhrase()
        ), true, $this->getStatusCode());

        // Send all of our headers
        foreach (array_keys($this->headers()) as $name) {
            header($name . ': ' . $this->getHeaderLine($name), false, $this->getStatusCode());
        }

        return $this;
    }

    /**
     * Sends the Body of the message to the browser
     * @return $this
     */
    public function sendBody()
    {
        echo $this->body;
        return $this;
    }

    /**
     * Perform a redirect to a new URL, in two flavors: header or location.
     * @param string $uri    URI to redirect to
     * @param int|null $code The type of redirection, defaults to 302
     * @return $this
     */
    public function redirect(string $uri, string $method = 'auto', ?int $code = null)
    {
        // IIS environment likely? Use 'refresh' for better compatibility
        if (
            $method === 'auto'
            && isset($_SERVER['SERVER_SOFTWARE'])
            && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false
        ) {
            $method = 'refresh';
        } elseif ($method !== 'refresh' && $code === null) {
            if (isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD'])) {
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $code = 302;
                } elseif (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'], true)) {
                    // reference: https://en.wikipedia.org/wiki/Post/Redirect/Get
                    $code = 303;
                } else {
                    $code = 307;
                }
            }
        }

        if ($code === null)
            $code = 302;

        switch ($method) {
            case 'refresh':
                $this->setHeader('Refresh', '0;url=' . $uri);
                break;

            default:
                $this->setHeader('Location', $uri);
                break;
        }

        $this->setStatusCode($code);

        return $this;
    }

    /**
     * Set a cookie
     *
     * @param array|Cookie|string $name Cookie name / array containing binds / Cookie object
     * @param string $value Cookie value
     * @param string $expire Cookie expiration time in seconds
     * @param string $domain Cookie domain (e.g.: '.yourdomain.com')
     * @param string $path Cookie path (default: '/')
     * @param string $prefix Cookie name prefix ('': the default prefix)
     * @param bool|null $secure Whether to only transfer cookies via SSL
     * @param bool|null $httponly Whether only make the cookie accessible via HTTP (no javascript)
     * @param string|null $samesite
     * @return $this
     */
    public function setCookie(
        $name,
        $value = '',
        $expire = '',
        $domain = '',
        $path = '/',
        $prefix = '',
        $secure = null,
        $httponly = null,
        $samesite = null
    ) {
        if ($name instanceof Cookie) {
            $this->cookieStore = $this->cookieStore->put($name);

            return $this;
        }

        /** @var CookieConfig|null $cookieConfig */
        $cookieConfig = config(CookieConfig::class);

        if ($cookieConfig instanceof CookieConfig) {
            $secure ??= $cookieConfig->secure;
            $httponly ??= $cookieConfig->httponly;
            $samesite ??= $cookieConfig->samesite;
        }

        if (is_array($name)) {
            // always leave 'name' in last place, as the loop will break otherwise, due to ${$item}
            foreach (['samesite', 'value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly', 'name'] as $item) {
                if (isset($name[$item])) {
                    ${$item} = $name[$item];
                }
            }
        }

        $cookie = new Cookie($name, $value, [
            'expires'  => $expire ?: 0,
            'domain'   => $domain,
            'path'     => $path,
            'prefix'   => $prefix,
            'secure'   => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite ?? '',
        ]);

        $this->cookieStore = $this->cookieStore->put($cookie);

        return $this;
    }

    /**
     * Returns the `CookieStore` instance
     * @return CookieStore
     */
    public function getCookieStore()
    {
        return $this->cookieStore;
    }

    /**
     * Checks to see if the Response has a specified cookie or not.
     */
    public function hasCookie(string $name, ?string $value = null, string $prefix = '')
    {
        $prefix = $prefix ?: Cookie::setDefaults()['prefix']; // to retain BC

        return $this->cookieStore->has($name, $prefix, $value);
    }

    /**
     * Returns the cookie
     * @param string $prefix Cookie prefix.
     *                       '': the default prefix
     * @return Cookie|Cookie[]|null
     */
    public function getCookie(?string $name = null, string $prefix = '')
    {
        if ((string) $name === '') {
            return $this->cookieStore->display();
        }

        try {
            $prefix = $prefix ?: Cookie::setDefaults()['prefix']; // to retain BC

            return $this->cookieStore->get($name, $prefix);
        } catch (Exception $e) {
            log_message('error', (string) $e);

            return null;
        }
    }

    /**
     * Sets a cookie to be deleted when the response is sent
     * @return $this
     */
    public function deleteCookie(string $name = '', string $domain = '', string $path = '/', string $prefix = '')
    {
        if ($name === '') {
            return $this;
        }

        $prefix = $prefix ?: Cookie::setDefaults()['prefix']; // to retain BC

        $prefixed = $prefix . $name;
        $store    = $this->cookieStore;
        $found    = false;

        /** @var Cookie $cookie */
        foreach ($store as $cookie) {
            if ($cookie->getPrefixedName() === $prefixed) {
                if ($domain !== $cookie->getDomain()) {
                    continue;
                }

                if ($path !== $cookie->getPath()) {
                    continue;
                }

                $cookie = $cookie->withValue('')->withExpired();
                $found  = true;

                $this->cookieStore = $store->put($cookie);
                break;
            }
        }

        if (!$found)
            $this->setCookie($name, '', '', $domain, $path, $prefix);

        return $this;
    }

    /**
     * Returns all cookies currently set
     * @return Cookie[]
     */
    public function getCookies()
    {
        return $this->cookieStore->display();
    }

    /**
     * Actually sets the cookies
     * @return void
     */
    protected function sendCookies()
    {
        $this->dispatchCookies();
    }

    private function dispatchCookies()
    {
        foreach ($this->cookieStore->display() as $cookie) {
            $name = $cookie->getPrefixedName();
            $value = $cookie->getValue();
            $options = $cookie->getOptions();

            if ($cookie->isRaw()) {
                $this->doSetRawCookie($name, $value, $options);
            } else {
                $this->doSetCookie($name, $value, $options);
            }
        }

        $this->cookieStore->clear();
    }

    /**
     * Extracted call to `setrawcookie()` in order to run unit tests on it
     */
    private function doSetRawCookie(string $name, string $value, array $options)
    {
        setrawcookie($name, $value, $options);
    }

    /**
     * Extracted call to `setcookie()` in order to run unit tests on it
     */
    private function doSetCookie(string $name, string $value, array $options)
    {
        setcookie($name, $value, $options);
    }
}
