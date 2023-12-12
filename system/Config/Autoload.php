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


    /**
     * Constructor
     * @param array $namespace List of Namespaces and its directory
     * @param array $files List of Files and its directory
     * @param array $helpers List of Helpers and its directory
     */
    public function __construct(?array $namespace = null, ?array $files = null, ?array $helpers = null)
    {
        if (isset($namespace)) $this->namespace = $namespace;
        if (isset($files)) $this->$files = $files;
        if (isset($helpers)) $this->helpers = $helpers;
    }

    /**
     * Register Class, Files, and Helpers
     * @return void
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * @return void
     */
    protected function loadClass($class)
    {
        foreach ($this->namespace as $namespace => $path) {
            $length = strlen($namespace);
            if (strncmp($namespace, $class, $length) !== 0) {
                continue;
            }

            $relativeClass = substr($class, $length);
            $filePath = $path . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($filePath)) {
                require_once $filePath;
                return;
            }
        }
    }
}
