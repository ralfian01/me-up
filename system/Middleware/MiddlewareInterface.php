<?php


namespace MVCME\Middleware;

use MVCME\Request\HTTPRequest;
use MVCME\Response\HTTPResponse;

/**
 * Middleware interface
 */
interface MiddlewareInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param array|null $arguments
     * @return HTTPRequest|HTTPResponse|string|void
     */
    public function before(HTTPRequest $request, $arguments = null);

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param array|null $arguments
     * @return HTTPResponse|void
     */
    public function after(HTTPRequest $request, HTTPResponse $response, $arguments = null);
}
