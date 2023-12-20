<?php

namespace App\Web;

use MVCME\Controller;
use MVCME\Request\HTTPRequestInterface;
use MVCME\Response\HTTPResponseInterface;

class BaseWeb extends Controller
{
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
     * An array of helpers to be loaded automatically upon class instantiation.
     * These helpers will be available to all other controllers that extend BaseController
     * @var array
     */
    protected $helpers = [];

    /**
     * @return void
     */
    public function initController(HTTPRequestInterface $request, HTTPResponseInterface $response)
    {
        // Do Not Edit This Line
        parent::initController($request, $response);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
    }
}
