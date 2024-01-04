<?php

namespace App\REST\V1;

class Home extends BaseRESTV1
{

    /**
     * @var object|null Database Repository
     */
    private $dbRepo;

    public function __construct(
        ?array $payload = [],
        ?array $file = [],
        ?array $auth = [],
        // ?DBRepository $dbRepo = null
    ) {

        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        // $this->dbRepo = $dbRepo ?? new DBRepository();
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
        $this->payload['id'] = $id;

        return $this->nextValidation();
    }

    private function nextValidation()
    {
        return $this->respond([
            "text" => 'hello world'
        ]);
    }
}
