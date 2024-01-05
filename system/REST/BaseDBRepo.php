<?php

namespace MVCME\REST;

abstract class BaseDBRepo
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
    abstract public function getData();

    /**
     * Function to insert new data to database
     * @return bool|array|object|null|string
     */
    abstract public function insertData();

    /**
     * Function to update data in database
     * @return bool|array|object|null|string
     */
    abstract public function updateData();

    /**
     * Function to delete data from database
     * @return bool|array|object|null|string
     */
    abstract public function deleteData();


    /**
     * Function to print detail of database exception
     * @return void
     */
    protected static function printDBException($exception)
    {
        // If project not in production environment, print error detail
        if ($_ENV['ENVIRONMENT'] != 'production')
            print_r($exception);
    }
}
