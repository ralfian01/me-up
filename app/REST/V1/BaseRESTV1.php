<?php

namespace App\REST\V1;

use Exception;
use MVCME\REST\BaseREST;
use MVCME\Request\Payload;
use MVCME\REST\BaseDBRepoInterface;

class BaseRESTV1 extends BaseREST implements BaseRESTV1Interface
{
    /**
     * @var array Property that contains the authentication data
     */
    public $auth = [];

    /**
     * @var array Property that contains the payload data in non file form
     */
    public $payload = [];

    /**
     * @var array Property that contains the privilege data
     */
    protected $privilegeRules = [];

    /**
     * @var array|Payload Property that contains the payload rules
     * (Please read the Payload class documentation for more information)
     */
    protected $payloadRules = [];

    /**
     * @var array|object Property that contain payload data in file form
     */
    protected $file = [];

    /**
     * @var BaseDBRepoInterface Property that contain database repository class
     */
    protected $dbRepo = [];

    /**
     * Default function if client unauthorized
     * @return void|string
     */
    private function __unauthorizedScheme()
    {
        return $this->error(401);
    }

    /**
     * Main activity
     * @return null
     */
    protected function mainActivity()
    {
        return null;
    }

    /** 
     * The main method called by routes
     * @return object|string
     */
    public function index()
    {
        // Collect payload in non file form and then combine it
        self::combinePayload($this->payload, $this->getPayload(), $this->payload);

        // Collect payload in file form
        $this->file = $this->getFile();

        // Collect authentication data
        $this->auth = $this->request->auth->data ?? [];

        if (isset($this->auth['authority'])) {

            // // Check account authority
            if (!$this->checkPrivilege($this->auth['authority']))
                return $this->__unauthorizedScheme();
        }

        $this->directCall = false;

        if (!method_exists(self::class, 'mainActivity')) {
            throw new Exception("Method mainActivity() does not exist");
        }

        $params = func_get_args();

        // Authorize client
        return $this->authHandler(
            fn () => $this->payloadChecker($params),
            fn () => $this->__unauthorizedScheme()
        );
    }


    /** 
     * Function to check payload
     * @return void
     */
    private function payloadChecker($param)
    {
        return (new Payload)
            ->setValidationData(
                $this->payload,
                $this->file,
                $this->payloadRules
            )
            ->validationSuccess(function () use ($param) {

                return $this->mainActivity(...$param);
            })
            ->validationFail(function ($err) {

                // Call default method when validation failed
                return $this->error(400, $err);
            })
            ->validate();
    }

    /**
     * Function to check account authorization
     * @return boolean
     */
    private function checkPrivilege($authority)
    {
        $validCount = 0;
        foreach ($this->privilegeRules as $key => $value) {
            if (in_array($value, $authority)) $validCount++;
        }

        return $validCount >= count($this->privilegeRules);
    }

    /** 
     * Function to combine initial payload and addon payload
     * @return array
     */
    private static function combinePayload(mixed &$return, array $payload, array $addon)
    {
        foreach ($addon as $key => $val) {
            $payload[$key] = $val;
        }

        $return = $payload;
    }
}
