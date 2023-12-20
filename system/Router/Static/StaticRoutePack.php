<?php

namespace MVCME\Router\Static;

use MVCME\Router\RoutePack;
use MVCME\Service\Services;
use stdClass;

/**
 * @method static RoutePack     get(string $from, $to, ?array $options = null)
 * @method static RoutePack     post(string $from, $to, ?array $options = null)
 * @method static RoutePack     put(string $from, $to, ?array $options = null)
 * @method static RoutePack     patch(string $from, $to, ?array $options = null)
 * @method static RoutePack     delete(string $from, $to, ?array $options = null)
 * @method static RoutePack     head(string $from, $to, ?array $options = null)
 * @method static RoutePack     options(string $from, $to, ?array $options = null)
 * @method static RoutePack     group(string $name, ...$params)
 * @method static RoutePack     match(array $verbs = [], string $from = '', $to = '', ?array $options = null)
 */
class StaticRoutePack
{
    /**
     * Route pack
     */
    protected RoutePack $routes;

    public static function __callStatic($name, $arguments)
    {
        $instance = new static;
        return $instance->setRoutes($name, $arguments);
    }

    public function __construct()
    {
        if (!isset($this->routes))
            $this->routes = Services::routes();
    }

    /**
     * Setup Routes
     */
    protected function setRoutes($name, $arguments)
    {
        return $this->routes->{$name}(
            $arguments[0],
            $arguments[1],
            $arguments[2] ?? null
        );
    }

    /**
     * @internal
     */
    protected static function routeConfig(string $hostname)
    {
        $return = new stdClass();

        if (preg_match('~^/[A-Za-z_-]+$~', $hostname)) {
            // Format: /<url>
            $return->segment = $hostname;
            $return->options = [];
        } elseif (preg_match('~^[A-Za-z_-]+\.[A-Za-z_-]+$~', $hostname)) {
            // Format: subdomain.domain
            $return->segment = '';
            $return->options = [
                'subdomain' => explode('.', $hostname)[0],
            ];
        } elseif (preg_match('~^[A-Za-z_-]+$~', $hostname)) {
            // Format: subdomain
            $return->segment = '';
            $return->options = [
                'subdomain' => $hostname,
            ];
        } else {
            $return->segment = '';
            $return->options = [];
        }

        return $return;
    }
}
