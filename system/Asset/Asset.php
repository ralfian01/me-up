<?php

namespace MVCME\Asset;

use Closure;
use MVCME\Router\Static\StaticRoutePack;
use MVCME\Config\App;
use MVCME\Config\Assets;
use MVCME\Router\RoutePackInterface;

class Asset extends StaticRoutePack
{
    /**
     * Application configuration
     * @var App
     */
    protected $config;

    /**
     * Application asset configuration
     * @var Assets
     */
    protected $asset;

    public function __construct(App $config, Assets $asset, RoutePackInterface $routes)
    {
        $this->config = $config;
        $this->routes = $routes;
        $this->asset = $asset;
    }

    public function loadAsset()
    {
        $routeConfig = self::routeConfig($this->config->assetHostname);

        $assetConfig = $this->asset;

        return $this->routes->group(
            $routeConfig->segment,
            $routeConfig->options,
            function ($routes) use ($assetConfig) {

                // 404 Asset
                $asset404 = $this->useValidController($assetConfig->asset404);
                $routes->get('/', $asset404);

                // Asset
                $assetController = $this->useValidController($assetConfig->assetController);
                $assetController = $this->addPlaceholder($assetConfig->assetController);

                $routes->get('(:any)', $assetController);
            }
        );
    }


    /**
     * Point the path to a valid controller
     * @return 
     */
    protected function useValidController($controller)
    {
        if (is_array($controller) && count($controller) >= 2) {
            return $controller;
        }

        if ($controller instanceof Closure) {
            return $controller;
        }

        if (substr($controller, 0, 1) !== '\\') {
            $controller = "\\" . $controller;
        }

        return $controller;
    }

    /**
     * Add placeholder to controller method
     * @return string|array|object
     */
    protected function addPlaceholder($controller)
    {
        if (is_array($controller) && count($controller) >= 2) {
            return $controller;
        }

        if ($controller instanceof Closure) {
            return $controller;
        }

        if (!strpos($controller, '/$')) {
            $controller .= '/$1';
        }

        return $controller;
    }

    /**
     * Get path directory of application asset
     * @return string
     */
    public function getAssetPath()
    {
        return $this->asset->assetPath;
    }

    /**
     * Get directory target of upload
     * @return string
     */
    public function getUploadPath()
    {
        return $this->asset->uploadPath;
    }

    /**
     * Get controller that handle request to application asset 
     * @return string
     */
    public function getAssetController()
    {
        return $this->asset->assetController;
    }
}
