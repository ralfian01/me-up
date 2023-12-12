<?php

namespace MVCME\Format;

use AppConfig\Format as FormatConfig;
use BadMethodCallException;
use InvalidArgumentException;

/**
 * The Format class is a convenient place to create Formatters
 */
class Format
{
    /**
     * Configuration instance
     */
    protected FormatConfig $config;

    /**
     * Constructor
     */
    public function __construct(FormatConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the current configuration instance
     * @return FormatConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * A Factory method to return the appropriate formatter for the given mime type
     */
    public function getFormatter(string $mime)
    {
        if (!array_key_exists($mime, $this->config->formatters))
            throw new InvalidArgumentException("Invalid Mime type");

        $className = $this->config->formatters[$mime];

        if (!class_exists($className))
            throw new BadMethodCallException("Invalid Formatter");

        $class = new $className();

        return $class;
    }
}
