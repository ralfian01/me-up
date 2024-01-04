<?php

namespace MVCME\Database;

use AppConfig\Database as DbConfig;
use InvalidArgumentException;

/**
 * Database Config
 */
class Config
{
    /**
     * Cache for instance of any connections that have been requested as a "shared" instance
     * @var array
     */
    protected static $instances = [];

    /**
     * The main instance used to manage all of our open database connections.
     * @var Database|null
     */
    protected static $dbBuilder;

    /**
     * Returns the database connection
     * @param array|DBConnection|string|null $group The name of the connection group to use,
     *                                                or an array of configuration settings.
     * @param bool $getShared Whether to return a shared instance of the connection.
     * @return DBConnection
     */
    public static function connect($group = null, bool $getShared = true)
    {
        $dbConfig = new DbConfig;

        if ($group === null)
            $group = $dbConfig->defaultGroup;

        assert(is_string($group));

        if (!isset($dbConfig->{$group})) {
            throw new InvalidArgumentException($group . ' is not a valid database connection group.');
        }

        $config = $dbConfig->{$group};

        if ($getShared && isset(static::$instances[$group])) {
            return static::$instances[$group];
        }

        if (!static::$dbBuilder instanceof Database) {
            static::$dbBuilder = new Database();
        }

        $connection = static::$dbBuilder->load($config, $group);

        static::$instances[$group] = $connection;

        return $connection;
    }

    /**
     * Returns an array of all db connections currently made.
     * @return array
     */
    public static function getConnections()
    {
        return static::$instances;
    }
}
