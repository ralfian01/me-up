<?php

namespace MVCME\Request;

/**
 * Request Trait
 */
trait RequestTrait
{
    /**
     * Stores values we've retrieved from PHP globals
     * @var array
     */
    protected $globals = [];

    /**
     * Fetch an item from the $_SERVER array
     * @param array|string|null $index Index for item to be fetched from $_SERVER
     * @param int|null $filter A filter name to be applied
     * @param array|int|null $flags
     * @return mixed
     */
    public function getServer($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('server', $index, $filter, $flags);
    }

    /**
     * Fetch an item from the $_ENV array
     * @param array|string|null $index Index for item to be fetched from $_ENV
     * @param int|null $filter A filter name to be applied
     * @param array|int|null $flags
     * @return mixed
     */
    public function getEnv($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('env', $index, $filter, $flags);
    }

    /**
     * Allows manually setting the value of PHP global, like $_GET, $_POST, etc
     * @param mixed $value
     * @return $this
     */
    public function setGlobal(string $method, $value)
    {
        $this->globals[$method] = $value;

        return $this;
    }

    /**
     * Fetches one or more items from a global, like cookies, get, post, etc.
     * Can optionally filter the input when you retrieve it by passing in
     * a filter.
     *
     * http://php.net/manual/en/filter.filters.sanitize.php
     *
     * @param string $method Input filter constant
     * @param array|string|null $index
     * @param int|null $filter Filter constant
     * @param array|int|null $flags Options
     * @return array|bool|float|int|object|string|null
     */
    public function fetchGlobal(string $method, $index = null, ?int $filter = null, $flags = null)
    {
        $method = strtolower($method);

        if (!isset($this->globals[$method])) {
            $this->collectGlobals($method);
        }

        // Null filters cause null values to return.
        $filter ??= FILTER_DEFAULT;
        $flags = is_array($flags) ? $flags : (is_numeric($flags) ? (int) $flags : 0);

        // Return all values when $index is null
        if ($index === null) {
            $values = [];

            foreach ($this->globals[$method] as $key => $value) {
                $values[$key] = is_array($value)
                    ? $this->fetchGlobal($method, $key, $filter, $flags)
                    : filter_var($value, $filter, $flags);
            }

            return $values;
        }

        // allow fetching multiple keys at once
        if (is_array($index)) {
            $output = [];

            foreach ($index as $key) {
                $output[$key] = $this->fetchGlobal($method, $key, $filter, $flags);
            }

            return $output;
        }

        // Does the index contain array notation?
        if (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) {
            $value = $this->globals[$method];

            for ($i = 0; $i < $count; $i++) {
                $key = trim($matches[0][$i], '[]');

                if ($key === '') { // Empty notation will return the value as array
                    break;
                }

                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }
        }

        if (!isset($value)) {
            $value = $this->globals[$method][$index] ?? null;
        }

        if (
            is_array($value)
            && (
                $filter !== FILTER_DEFAULT
                || (
                    (is_numeric($flags) && $flags !== 0)
                    || is_array($flags) && $flags !== []
                )
            )
        ) {
            // Iterate over array and append filter and flags
            array_walk_recursive($value, static function (&$val) use ($filter, $flags) {
                $val = filter_var($val, $filter, $flags);
            });

            return $value;
        }

        // Cannot filter these types of data automatically...
        if (is_array($value) || is_object($value) || $value === null) {
            return $value;
        }

        return filter_var($value, $filter, $flags);
    }

    /**
     * @return void
     * 
     * @deprecated Method renamed to collectGlobals
     */
    protected function populateGlobals(string $method)
    {
        $this->collectGlobals($method);
    }

    /**
     * Saves a copy of the current state of one of several PHP globals so we can retrieve them later
     * @return void
     */
    protected function collectGlobals(string $method)
    {
        if (!isset($this->globals[$method])) {
            $this->globals[$method] = [];
        }

        // Don't populate ENV as it might contain
        // sensitive data that we don't want to get logged.
        switch ($method) {
            case 'get':
                $this->globals['get'] = $_GET;
                break;

            case 'post':
                $this->globals['post'] = $_POST;
                break;

            case 'request':
                $this->globals['request'] = $_REQUEST;
                break;

            case 'cookie':
                $this->globals['cookie'] = $_COOKIE;
                break;

            case 'server':
                $this->globals['server'] = $_SERVER;
                break;
        }
    }
}
