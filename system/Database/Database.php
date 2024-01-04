<?php

namespace MVCME\Database;

use InvalidArgumentException;

/**
 * Database Connection.
 * Creates and returns an instance of the appropriate Database Connection.
 */
class Database
{
    /**
     * Maintains an array of the instances of all connections that have been created.     *
     * @var array
     */
    protected $connections = [];

    /**
     * Parses the connection binds and creates a Database Connection instance.
     * @return BaseConnection
     */
    public function load(array $params = [], string $alias = '')
    {
        if ($alias === '') {
            throw new InvalidArgumentException('You must supply the parameter: alias.');
        }

        if (!empty($params['DSN']) && strpos($params['DSN'], '://') !== false) {
            $params = $this->parseDSN($params);
        }

        if (empty($params['DBDriver'])) {
            throw new InvalidArgumentException('You have not selected a database type to connect to.');
        }

        $this->connections[$alias] = $this->initDriver($params['DBDriver'], 'Connection', $params);

        return $this->connections[$alias];
    }

    /**
     * Creates an instance of Utils for the current database type.
     * @return BaseUtils
     */
    public function loadUtils(DBConnectionInterface $db)
    {
        if (!$db->connID) {
            $db->initialize();
        }

        return $this->initDriver($db->DBDriver, 'Utils', $db);
    }

    /**
     * Parses universal DSN string
     * @throws InvalidArgumentException
     */
    protected function parseDSN(array $params): array
    {
        $dsn = parse_url($params['DSN']);

        if (!$dsn) {
            throw new InvalidArgumentException('Your DSN connection string is invalid.');
        }

        $dsnParams = [
            'DSN' => '',
            'DBDriver' => $dsn['scheme'],
            'hostname' => isset($dsn['host']) ? rawurldecode($dsn['host']) : '',
            'port' => isset($dsn['port']) ? rawurldecode((string) $dsn['port']) : '',
            'username' => isset($dsn['user']) ? rawurldecode($dsn['user']) : '',
            'password' => isset($dsn['pass']) ? rawurldecode($dsn['pass']) : '',
            'database' => isset($dsn['path']) ? rawurldecode(substr($dsn['path'], 1)) : '',
        ];

        if (!empty($dsn['query'])) {
            parse_str($dsn['query'], $extra);

            foreach ($extra as $key => $val) {
                if (is_string($val) && in_array(strtolower($val), ['true', 'false', 'null'], true)) {
                    $val = $val === 'null' ? null : filter_var($val, FILTER_VALIDATE_BOOLEAN);
                }

                $dsnParams[$key] = $val;
            }
        }

        return array_merge($params, $dsnParams);
    }

    /**
     * Creates a database object
     * @param string $driver Driver name
     * @param string $class 'Connection'|'Forge'|'Utils'
     * @param array|object $argument The constructor parameter.
     * @return BaseConnection|BaseUtils|Forge
     */
    protected function initDriver(string $driver, string $class, $argument)
    {
        $classname = (strpos($driver, '\\') === false)
            ? "MVCME\\Database\\{$driver}\\{$class}"
            : $driver . '\\' . $class;

        return new $classname($argument);
    }
}
