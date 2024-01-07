<?php

/**
 *---------------------------------------------------------------
 * ROOT FILE
 *---------------------------------------------------------------
 * Set path, autoloader and framework constants
 */

// Location of class Paths file
$pathFile = __DIR__ . DIRECTORY_SEPARATOR . "../config/AppConfig/Paths.php";

### Import path configurator
require_once($pathFile);
$paths = new AppConfig\Paths();

### Import MVC_ME systemWrapper.php
require_once($paths->systemDir . DIRECTORY_SEPARATOR . "systemWrapper.php");


// Location of class EnvFile file
$envFile = SYSTEMPATH . "Config/EnvFile.php";

### Import Environment loader class
require_once($envFile);
(new MVCME\Config\EnvFile(ROOTPATH, '.env'))->load();

### Define ENVIRONMENT constant
if (!defined('ENVIRONMENT'))
    define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? $_SERVER['ENVIRONMENT'] ?? 'production');


// Location of composer autoload
$composerAutoload = SYSTEMPATH . "../../../autoload.php";

### Import Composer autoload
if (file_exists($composerAutoload))
    require_once($composerAutoload);


/*
 * ---------------------------------------------------------------
 * INITIALIZE & RUN THE APP
 * ---------------------------------------------------------------
 */

$app = new MVCME\MVCME(new AppConfig\App());

// Initialize the application with the configuration of the App() class and Environment files
$app->initialize();

// Run the app
$app->fire();
