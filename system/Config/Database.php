<?php

namespace MVCME\Config;

use MVCME\Database\Config;

class Database extends Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'driver'       => 'MySQLi',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8',
        'DBCollat'     => 'utf8_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
    ];

    public function __construct()
    {
        $this->mergeEnv();
    }

    /**
     * Combine Application configuration with Environment files.
     * Some configurations have to be set inside the App class
     * @return void
     */
    private function mergeEnv()
    {
        if (isset($_ENV['database']['default']['hostname'])) $this->default['hostname'] = $_ENV['database']['default']['hostname'];
        if (isset($_ENV['database']['default']['database'])) $this->default['database'] = $_ENV['database']['default']['database'];
        if (isset($_ENV['database']['default']['username'])) $this->default['username'] = $_ENV['database']['default']['username'];
        if (isset($_ENV['database']['default']['password'])) $this->default['password'] = $_ENV['database']['default']['password'];
        if (isset($_ENV['database']['default']['port'])) $this->default['port'] = $_ENV['database']['default']['port'];
        if (isset($_ENV['database']['default']['driver'])) $this->default['driver'] = $_ENV['database']['default']['driver'];
    }
}
