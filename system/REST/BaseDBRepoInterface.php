<?php

namespace MVCME\REST;

interface BaseDBRepoInterface
{
    /**
     * Function to get data from database
     * @return bool|array|object|null|string
     */
    public function getData();

    /**
     * Function to insert new data to database
     * @return bool|array|object|null|string
     */
    public function insertData();

    /**
     * Function to update data in database
     * @return bool|array|object|null|string
     */
    public function updateData();

    /**
     * Function to delete data from database
     * @return bool|array|object|null|string
     */
    public function deleteData();
}
