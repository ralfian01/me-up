<?php

namespace MVCME\Router\Static;

use AppConfig\App;

class Web extends StaticRoutePack
{
    protected function setRoutes($name, $arguments)
    {
        $config = new App;
        $routeConfig = self::routeConfig($config->hostname);

        return $this->routes->group(
            '',
            [],
            static function ($routes) use ($name, $arguments) {

                return $routes->{$name}(...$arguments);
            }
        );
    }
}
