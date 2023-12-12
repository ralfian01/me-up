<?php

namespace MVCME;

/**
 * Global Constants
 */
final class GlobalConstants
{
    private array $server;
    private array $get;

    public function __construct(?array $server = null, ?array $get = null)
    {
        $this->server = $server ?? $_SERVER;
        $this->get = $get ?? $_GET;
    }

    /**
     * Get the value of a specific global constant.
     * This method will retrieve the value of the $_SERVER constant
     * @return array|string|null
     */
    public function getServer(string $key)
    {
        return $this->server[$key] ?? null;
    }

    /**
     * Retrieve all values of the $_SERVER constant.
     * @return array|null
     */
    public function getServerAll()
    {
        return $this->server ?? null;
    }

    /**
     * Set the new value of the global constant specifically.
     * This method will affect the value of the $_SERVER constant
     * @return void
     */
    public function setServer(string $key, string $value)
    {
        $this->server[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * Get the value of a specific global constant.
     * This method will retrieve the value of the $_GET constant
     * @return array|string|null
     */
    public function getGet(string $key)
    {
        return $this->get[$key] ?? null;
    }

    /**
     * Retrieve all values of the $_GET constant
     * @return array|null
     */
    public function getGetAll()
    {
        return $this->get ?? null;
    }

    /**
     * Set the new value of the global constant specifically.
     * This method will affect the value of the $_GET constant
     * @return void
     */
    public function setGet(string $key, string $value)
    {
        $this->get[$key] = $value;
        $_GET[$key] = $value;
    }

    /**
     * Set the new value of the global constant in an array.
     * This method will affect the value of the $_GET constant
     * @return void
     */
    public function setGetArray(array $array)
    {
        $this->get = $array;
        $_GET = $array;
    }
}
