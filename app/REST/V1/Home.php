<?php

namespace App\REST\V1;

class Home extends BaseRESTV1
{
    public function __construct(
        ?array $payload = [],
        ?array $file = [],
        ?array $auth = [],
        // ?DBRepo $dbRepo = null
    ) {

        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        // $this->dbRepo = $dbRepo ?? new DBRepo();
        return $this;
    }

    /* Edit this line to set payload rules */
    protected $payloadRules = [
        'uuid' => ['base64'],
    ];

    /* Edit this line to set authority rules */
    protected $privilegeRules = [];


    protected function mainActivity($id = null)
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        return $this->respond([
            "text" => 'hello world'
        ]);
    }
}
