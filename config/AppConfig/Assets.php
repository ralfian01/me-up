<?php

namespace AppConfig;

use MVCME\Config\Assets as ConfigAssets;

class Assets extends ConfigAssets
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

    // /**
    //  * Controller to handle request to application asset
    //  * @var array|string
    //  * 
    //  * Example:
    //  * - 'App\Asset::index'
    //  * - [Asset::class, 'index']
    //  */
    // public $assetController = 'App\Asset::index';

    // /**
    //  * Method controller to handle request to application asset
    //  * @var array|string
    //  * 
    //  * Example:
    //  * - 'App\Asset::error404'
    //  * - [Asset::class, 'error404']
    //  */
    // public $asset404 = 'App\Asset::error404';
}
