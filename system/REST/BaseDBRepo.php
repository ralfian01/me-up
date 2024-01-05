<?php

namespace MVCME\REST;

class BaseDBRepo implements BaseDBRepoInterface
{

    /**
     * @var array Payload
     */
    protected $payload;

    /**
     * @var array|object File
     */
    protected $file;

    /**
     * @var array|object Authentication
     */
    protected $auth;

    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
    }

    /**
     * Function to get data from database
     * @return bool|array|object|null|string
     */
    public function getData()
    {
        return null;
    }

    /**
     * Function to insert new data to database
     * @return bool|array|object|null|string
     */
    public function insertData()
    {
        return null;
    }

    /**
     * Function to update data in database
     * @return bool|array|object|null|string
     */
    public function updateData()
    {
        return null;
    }

    /**
     * Function to delete data from database
     * @return bool|array|object|null|string
     */
    public function deleteData()
    {
        return null;
    }


    /**
     * Function to print detail of database exception
     * @return void
     */
    protected function printDBException($exception)
    {
        // If project not in production environment, print error detail
        if ($_ENV['CI_ENVIRONMENT'] != 'production')
            print_r($exception);
    }
}
