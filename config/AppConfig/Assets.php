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
}
