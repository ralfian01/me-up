<?php

namespace MVCME\Router\Static;

use AppConfig\App;

class REST extends StaticRoutePack
{
    protected function setRoutes($name, $arguments)
    {
        $config = new App;
        $routeConfig = self::routeConfig($config->apiHostname);

        return $this->routes->group(
            $routeConfig->segment,
            $routeConfig->options,
            static function ($routes) use ($name, $arguments) {

                return $routes->{$name}(...$arguments);
            }
        );
    }

    /**
     * @param Closure|array|null|string $callable
     */
    public static function setDefault404($callable = null)
    {
        self::get('(:any)', $callable);
    }
}
