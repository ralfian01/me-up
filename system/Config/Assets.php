<?php

namespace MVCME\Config;

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
}
