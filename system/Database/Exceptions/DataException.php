<?php

namespace MVCME\Database\Exceptions;

use RuntimeException;

class DataException extends RuntimeException
{
    /**
     * Used by Model's insert/update methods when there isn't any data to actually work with. 
     * @return DataException
     */
    public static function emptyDataset($message)
    {
        return $message;
    }

    /**
     * Used by Model's insert/update methods when there is no primary key defined
     * and Model has option `useAutoIncrement`
     * set to false.
     * @return DataException
     */
    public static function emptyPrimaryKey()
    {
        return "Model has no primary key";
    }

    public static function invalidAllowedFields()
    {
        return "You attempted to edit a column that is not permitted";
    }
}
