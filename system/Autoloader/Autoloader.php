<?php

namespace MVCME\Autoloader;

use MVCME\Config\Autoload;

class Autoloader
{
    /**
     * @var Autoload Autoload configuration
     */
    protected $config;

    /**
     * @var array List of Namespaces and directory Paths
     */
    public $namespace = [];

    /**
     * @var array Files
     */
    public $files = [];

    /**
     * @var array Helpers
     */
    public $helpers = [];

    /**
     * Constructor
     */
    public function __construct(Autoload $config)
    {
        $this->namespace = $config->namespace;
        $this->files = $config->files;
        $this->helpers = $config->helpers;

        // Load helpers
        $this->loadHelpers($this->helpers);
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

    /**
     * @return void
     */
    public function loadHelpers($filenames)
    {
        if (!is_array($filenames)) {
            $filenames = [$filenames];
        }

        foreach ($filenames as $filename) {
            if (!strpos($filename, '_helper')) {
                $filename .= '_helper';
            }

            if (is_file(APPPATH . "Helpers/{$filename}.php")) {
                require_once(APPPATH . "Helpers/{$filename}.php");
            } elseif (is_file(SYSTEMPATH . "Helpers/{$filename}.php")) {
                require_once(SYSTEMPATH . "Helpers/{$filename}.php");
            }
        }
    }
}
