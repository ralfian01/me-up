<?php

namespace MVCME\Config;

use MVCME\Asset\AssetController;

/**
 * Application Asset configuration
 */
class Assets
{
    /**
     * Asset path
     * @var string
     */
    public $assetPath = ROOTPATH . "assets";

    /**
     * Destination directory when uploading files
     * @var string
     */
    public $uploadPath = ROOTPATH . "assets/uploads";

    /**
     * Controller to handle request to application asset
     * @var array|string
     * 
     * Example:
     * - 'App\Asset::index'
     * - [Asset::class, 'index']
     */
    public $assetController = [AssetController::class, 'index'];

    /**
     * Method controller to handle request to application asset
     * @var array|string
     * 
     * Example:
     * - 'App\Asset::error404'
     * - [Asset::class, 'error404']
     */
    public $asset404 = [AssetController::class, 'error404'];
}
