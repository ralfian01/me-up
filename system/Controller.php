<?php

namespace MVCME;

use MVCME\Request\HTTPRequestInterface;
use MVCME\Response\HTTPResponseInterface;

/**
 * Class Controller
 */
class Controller
{
    /**
     * Helpers that will be automatically loaded on class instantiation
     * @var array
     */
    protected $helpers = [];

    /**
     * Instance of the main Request object
     * @var HTTPRequestInterface|null
     */
    protected $request;

    /**
     * Instance of the main response object
     * @var HTTPResponseInterface|null
     */
    protected $response;

    /**
     * Should enforce HTTPS access for all methods in this controller
     */
    protected int $forceHTTPS = 0;

    /**
     * Once validation has been run, will hold the Validation instance
     * @var ValidationInterface
     */
    protected $validator;

    /**
     * Constructor
     * @return void
     */
    public function initController(HTTPRequestInterface $request, HTTPResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;

        // if ($this->forceHTTPS > 0)
        //     $this->forceHTTPS($this->forceHTTPS);

        // // Autoload helper files.
        // helper($this->helpers);
    }

    /**
     * A convenience method to use when you need to ensure that a single
     * method is reached only via HTTPS. If it isn't, then a redirect
     * will happen back to this method and HSTS header will be sent
     * to have modern browsers transform requests automatically.
     *
     * @param int $duration The number of seconds this link should be
     *                      considered secure for. Only with HSTS header.
     *                      Default value is 1 year.
     *
     * @return void
     *
     * @throws HTTPException
     */
    protected function forceHTTPS(int $duration = 31_536_000)
    {
        force_https($duration, $this->request, $this->response);
    }
}
