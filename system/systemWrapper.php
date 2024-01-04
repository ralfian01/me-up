<?php

use AppConfig\Paths;
use AppConfig\Autoload;

// Object of path constants
if (!isset($paths))
    $paths = new Paths();

/*
 * ---------------------------------------------------------------
 * INITIATION PATH CONSTANTS
 * ---------------------------------------------------------------
 */

// Path to the application directory.
if (!defined('APPPATH'))
    define('APPPATH', realpath($paths->appDir) . DIRECTORY_SEPARATOR);

// Path to the project root directory.
if (!defined('ROOTPATH'))
    define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);

// Path to the app configuration directory.
if (!defined('CONFIGPATH'))
    define('CONFIGPATH', realpath($paths->configDir) . DIRECTORY_SEPARATOR);

// Path to the app routes configuration directory.
if (!defined('ROUTESPATH'))
    define('ROUTESPATH', realpath(CONFIGPATH . '/Routes') . DIRECTORY_SEPARATOR);

// Path to the system directory.
if (!defined('SYSTEMPATH'))
    define('SYSTEMPATH', realpath($paths->systemDir) . DIRECTORY_SEPARATOR);

/*
 * ---------------------------------------------------------------
 * VARIABLE CONSTANTS
 * ---------------------------------------------------------------
 */

if (!defined('APP_NAMESPACE'))
    require(CONFIGPATH . "AppConfig/Constants.php");

// Temporary
// require_once(SYSTEMPATH . "Common.php");


/*
 * ---------------------------------------------------------------
 * IMPORT AUTOLOADER
 * ---------------------------------------------------------------
 * 
 * Combines all files that play a role in the application into one
 */

if (!class_exists(Autoload::class, false)) {
    require_once(SYSTEMPATH . "Config/Autoload.php");
    require_once(CONFIGPATH . "AppConfig/Autoload.php");
}

// Run Autoloader
(new Autoload())->register();
