<?php

namespace AppConfig;

use MVCME\Config\Autoload as ConfigAutoload;

/**
 * Autoloader Configuration
 */
class Autoload extends ConfigAutoload
{
    /**
     * List of Namespaces and directory Paths
     * 
     * Prototype:
     *   $psr4 = [
     *       'MVCME'    => SYSTEMPATH,
     *       'App'      => APPPATH
     *   ];
     */
    public array $namespace = [
        'MVCME' => SYSTEMPATH,
        APP_NAMESPACE => APPPATH,
        'AppConfig' => CONFIGPATH . 'AppConfig',
        'AppRoutes' => CONFIGPATH . 'Routes',
        'Config' => CONFIGPATH . 'Config',
    ];

    /**
     * Files
     * 
     * Prototype:
     *   $files = [
     *       '/path/to/my/file.php',
     *   ];
     */
    public array $files = [];

    /**
     * Helpers
     * 
     * Prototype:
     *   $helpers = [
     *       'form',
     *   ];
     */
    public array $helpers = [];
}
