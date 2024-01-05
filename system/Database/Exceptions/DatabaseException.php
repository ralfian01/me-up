<?php

namespace MVCME\Database\Exceptions;

use Error;

class DatabaseException extends Error
{
    /**
     * @return int
     */
    public function getExitCode()
    {
        return 0;
    }
}
