<?php

namespace MVCME\Database\MySQLi;

use MVCME\Database\DBUtils;
use Exception;

/**
 * Utils for MySQLi
 */
class Utils extends DBUtils
{
    /**
     * List databases statement
     *
     * @var string
     */
    protected $listDatabases = 'SHOW DATABASES';

    /**
     * OPTIMIZE TABLE statement
     *
     * @var string
     */
    protected $optimizeTable = 'OPTIMIZE TABLE %s';

    /**
     * Platform dependent version of the backup function.
     *
     * @return never
     */
    public function _backup(?array $prefs = null)
    {
        throw new Exception('Unsupported feature of the database platform you are using.');
    }
}
