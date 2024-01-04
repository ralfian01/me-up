<?php

namespace MVCME\HTTP;

use MVCME\Config\App;
use MVCME\GlobalConstants;
use MVCME\Message\Message;

class CORS extends Message
{
    /**
     * @var App Configuration
     */
    protected $config;

    /**
     * @var GlobalConstants Configuration
     */
    protected $globalConstants;

    /**
     * @var array Allowed origins
     */
    private $allowedOrigins = [];

    /**
     * @var array Allowed headers
     */
    private $allowedHeaders = [];

    /**
     * @var array Allowed HTTP methods
     */
    private $allowedMethods = [];


    public function __construct(?App $config, ?GlobalConstants $globalConstants)
    {
        $this->config = $config;
        $this->globalConstants = $globalConstants;

        $this->allowedOrigins = array_merge($this->allowedOrigins, $this->config->allowedURLs);
        $this->allowedHeaders = array_merge($this->allowedHeaders, $this->config->allowedHeaders);
        $this->allowedMethods = array_merge($this->allowedMethods, $this->config->allowedHTTPMethod);
    }


    /**
     * Apply header CORS 
     * @return void
     */
    public function applyCors()
    {
        if (strtolower($this->config->environment) == 'development') {
            // 
        }

        $this->applyAccessControlAllowOrigin();
        $this->applyAccessControlAllowHeaders();
        $this->applyAccessControlAllowMethods();
    }


    /*
     * -------------------------------------------------------------
     * HEADER ACCESS CONTROL ALLOW ORIGIN
     * -------------------------------------------------------------
    */

    /**
     * @return string|null
     */
    protected function getAccessControlAllowOrigin()
    {
        $httpOrigin = $this->globalConstants->getServer('http_origin');

        if ($httpOrigin !== null) {
            if (in_array($httpOrigin, $this->allowedOrigins))
                return $httpOrigin;
        }

        return null;
    }

    /**
     * @return void
     */
    protected function applyAccessControlAllowOrigin()
    {
        $accControlOrigin = $this->getAccessControlAllowOrigin();

        if ($accControlOrigin !== null) {
            header('Access-Control-Allow-Origin: ' . $accControlOrigin);
        }
    }


    /*
     * -------------------------------------------------------------
     * HEADER ACCESS CONTROL ALLOW HEADERS
     * -------------------------------------------------------------
    */

    /**
     * @return string|null
     */
    protected function getAccessControlAllowHeaders()
    {
        if (count($this->allowedHeaders) >= 1) {
            return implode(', ', $this->allowedHeaders);
        }

        return null;
    }

    /**
     * @return void
     */
    protected function applyAccessControlAllowHeaders()
    {
        $accControlHeaders = $this->getAccessControlAllowHeaders();

        if ($accControlHeaders !== null) {
            header('Access-Control-Allow-Headers: ' . $accControlHeaders);
        }
    }


    /*
     * -------------------------------------------------------------
     * HEADER ACCESS CONTROL ALLOW METHODS
     * -------------------------------------------------------------
    */

    /**
     * @return string|null
     */
    protected function getAccessControlAllowMethods()
    {
        if (count($this->allowedMethods) >= 1) {
            return implode(', ', $this->allowedMethods);
        }

        return null;
    }

    /**
     * @return void
     */
    protected function applyAccessControlAllowMethods()
    {
        $accControlMethods = $this->getAccessControlAllowMethods();

        if ($accControlMethods !== null) {
            header('Access-Control-Allow-Origin: ' . $accControlMethods);
        }
    }
}
