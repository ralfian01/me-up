<?php

namespace MVCME\REST;

use AppConfig\REST;
use MVCME\Controller;
use MVCME\Request\HTTPRequestInterface;
use MVCME\Response\HTTPResponseInterface;
use MVCME\Service\Services;
use MVCME\Request\Payload;

class BaseREST extends Controller
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
     * Set valid origin when access API to show report_id
     */
    protected $validOrigin = [
        'localhost:9000',
        'localhost:9000/pts_api'
    ];

    /**
     * @var bool $directCall Whether the class is called via the index method or not
     */
    protected $directCall = true;

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

    /**
     * @var ResponStatuses $errStatus
     */
    private $errStatus = [
        400 => [
            'status' => 'BAD_REQUEST',
            'description' => 'Bad Request'
        ],
        401 => [
            'status' => 'UNAUTHORIZED',
            'description' => 'You do not have authorization to access this resource'
        ],
        403 => [
            'status' => 'FORBIDDEN',
            'description' => 'You are prohibited from accessing these resources'
        ],
        404 => [
            'status' => 'NOT_FOUND',
            'description' => 'URL not available'
        ],
        409 => [
            'status' => 'CONFLICT',
            'description' => 'Data already exists in the system'
        ],
        500 => [
            'status' => 'INTERNAL_ERROR',
            'description' => 'There is an error in internal server'
        ]
    ];

    /**
     * Function to set error status
     * @return void
     */
    private $setErrorStatus = false;
    public function setErrorStatus(string|int $code, array $status)
    {

        foreach ($status as $key => $val) {
            $this->errStatus[$code][$key] = $val;
        }

        $this->setErrorStatus = true;
        return $this;
    }

    /**
     * Function contains json template to provide error response
     * @return void
     */
    public function error(int $code = 400, array $detail = [], ?string $report_id = null)
    {

        $this->setErrorStatus = false;

        $code = intval($code);
        $json = [
            'code' => $code,
            'status' => $this->errStatus[$code]['status'],
            'description' => $this->errStatus[$code]['description'],
            'error_detail' => $detail,
        ];

        if (isset($report_id)) {
            if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $this->validOrigin))
                $json['report_id'] = $report_id;
        }

        // Apply CORS
        Services::CORS(new REST)->applyCors();

        if (!$this->directCall) {

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode($code)
                ->setBody(json_encode($json, JSON_PRETTY_PRINT));
        }

        return $json;
    }

    /**
     * @var ResponStatuses $respondStatus
     */
    private $respondStatus = [
        200 => [
            'status' => 'SUCCESS',
        ],
        201 => [
            'status' => 'CREATED',
        ],
        202 => [
            'status' => 'ACCEPTED',
        ],
        204 => [
            'status' => 'NO_CONTENT',
        ],
    ];

    /**
     * Function contains json template response
     * @return void
     */
    public function respond(array|int $data_or_code, array $data = [])
    {

        $code = is_int($data_or_code) ? $data_or_code : 200;
        $data = is_array($data_or_code) ? $data_or_code : $data;

        // Apply CORS
        Services::CORS(new REST)->applyCors();

        if (!$this->directCall) {

            return $this->response
                ->setContentType('application/json')
                ->setStatusCode($code)
                ->setBody(json_encode(
                    [
                        'code' => $code,
                        'status' => $this->respondStatus[$code]['status'],
                        'data' => $data
                    ],
                    JSON_PRETTY_PRINT
                ));
        }

        return $data;
    }


    /** 
     * Function to return payload array based on request method and content type
     * This method can only return a payload array in non-file form
     * @return array
     */
    public function getPayload()
    {

        // If request payload is json
        if ($this->request->is('json')) return $this->request->getJSON(true);

        $contentType = $this->request->getHeaderLine('content-type');
        $requestMethod = strtoupper($this->request->getServer('REQUEST_METHOD'));

        switch ($requestMethod) {

            case 'GET':

                // GET method only support x-www-form-urlencoded and common content type
                if (strpos($contentType, 'x-www-form-urlencoded') >= 1)
                    return $this->request->getRawInput();

                return $this->request->getPostGet();
                break;
            case 'POST':

                // POST method support form-data, x-www-form-urlencoded, and common content type
                return $this->request->getGetPost();
                break;
            case 'PUT':
            case 'PATCH':
            case 'DELETE':

                // PUT & PATCH method support form-data, x-www-form-urlencoded, and common content type
                if (strpos($contentType, 'form-data') >= 1) {

                    $formData = file_get_contents('php://input');
                    return Payload::parseFormData($formData);
                }

                if (strpos($contentType, 'x-www-form-urlencoded'))
                    return $this->request->getRawInput();
                break;
        }

        return [];
    }

    /** 
     * Function to return an array of files sent in formdata
     * This method can only check files in formdata format 
     * and with http post, put, or patch methods  
     * @return array
     */
    public function getFile(string $fileName = null)
    {

        $contentType = $this->request->getHeaderLine('content-type');
        $requestMethod = strtoupper($this->request->getServer('REQUEST_METHOD'));

        if (strpos($contentType, 'form-data') >= 1) {

            switch ($requestMethod) {

                case 'POST':

                    if ($fileName != null) return $this->request->getFile($fileName);
                    return $this->request->getFiles();
                    break;
                case 'PUT':
                case 'PATCH':
                case 'DELETE':

                    $formData = file_get_contents('php://input');
                    $data = Payload::parseFormDataFile($formData);

                    return $data;
                    break;
            }
        }

        return [];
    }

    /**
     * @param callable  $scFunction     Callback function triggered when process success
     * @param callable  $errFunction    Callback function triggered when process error or failed
     * 
     * @todo callback function $errFunction must be defined if you want to do something to client when they are not authenticated
     */
    public function authHandler(callable $scFunction, callable $errFunction, ...$options)
    {

        /*
         * !!! Note:
         * callback function $errFunction must be defined
         * if you want to do something to client when they
         * are not authenticated
         * 
        */

        // Compile all arguments
        $param = func_get_args();

        // $err = new Error($this->response, $this->request);

        // If client not authenticated
        if (isset($this->request->auth)) {

            $param['auth'] = $this->request->auth;
            $param['uri_segment'] = $_SERVER['REQUEST_URI'];

            if (!$this->request->auth->status) {

                if (is_callable($errFunction)) return $errFunction($param);
                return $this->error(500);
            }

            // If client authenticated
            if (is_callable($scFunction)) return $scFunction($param);
            return $this->error(500);
        } else {

            // If client not authenticated
            if (is_callable($scFunction)) return $scFunction($param);

            return $this->error(500);
        }
    }
}
