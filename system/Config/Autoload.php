<?php

namespace MVCME\Config;

class Autoload
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
        'AppConfig' => CONFIGPATH . 'AppConfig',
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
