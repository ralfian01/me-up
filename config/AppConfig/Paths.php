<?php

namespace AppConfig;

/**
 * Sets the file source path that supports the application system
 */
class Paths
{
    /**
     * System Directory
     */
    public string $systemDir = __DIR__ . DIRECTORY_SEPARATOR . '../../system';

    /**
     * App Directory
     */
    public string $appDir = __DIR__ . DIRECTORY_SEPARATOR . '../../app';

    /**
     * App Configuration Directory
     */
    public string $configDir = __DIR__ . DIRECTORY_SEPARATOR . '../../config';

    /**
     * View Directory
     */
    public string $viewDir = __DIR__ . DIRECTORY_SEPARATOR . 'Views';
}
