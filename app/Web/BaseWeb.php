<?php

namespace App\Web;

use MVCME\Controller;
use MVCME\Request\HTTPRequestInterface;
use MVCME\Response\HTTPResponseInterface;

class BaseWeb extends Controller
{
    /**
     * Helpers that will be automatically loaded on class instantiation
     * @var array
     */
    protected $helpers = [];

    /**
     * Helpers that will be automatically loaded on class instantiation
     * @var string
     */
    protected $baseViewPath;

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
