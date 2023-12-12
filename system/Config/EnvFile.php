<?php

namespace MVCME\Config;

/**
 * Environment-specific configuration
 */
class EnvFile
{
    /**
     * The directory where the .env located.
     * @var string
     */
    protected $envPath;

    /**
     * Constructor
     */
    public function __construct(?string $envPath = null, ?string $file = '.env')
    {
        $this->envPath = $envPath . $file;
    }

    /**
     * Load the settings in the .env file
     * @return bool
     */
    public function load()
    {
        $variables = $this->parse();
        return $variables !== null;
    }

    /**
     * Parse the .env file into an array of key => value
     * @return array|null
     */
    public function parse()
    {

        // Make sure the .env file is in the project root
        if (!file_exists($this->envPath))
            return null;

        // Make sure .env file is readable
        if (!is_readable($this->envPath))
            throw new \Exception("The Environment file cannot be read. File Path: \"{$this->envPath}\"");

        $variables = $this->parseEnvFile($this->envPath);

        $this->setVariable($variables);

        return $variables;
    }

    /**
     * Parse Environment File
     * @return array|null
     */
    private function parseEnvFile(string $envPath)
    {

        $data = file_get_contents($envPath);

        $lineBreak = ["\n", "\r", "\r\n", "\n\r", PHP_EOL];
        $data = str_replace($lineBreak, "\\r\\n", $data);
        $lines = explode("\\r\\n", $data);

        $pattern = '/^\s*([\w.-]+)\s*=\s*(.*)\s*$/m';
        $result = [];

        foreach ($lines as $line) {
            preg_match_all($pattern, $line, $blocks, PREG_SET_ORDER);

            if (is_array($blocks) && count($blocks) >= 1) {
                foreach ($blocks as $block) {
                    $key = $block[1];
                    $value = $block[2];

                    // Define env key with value
                    $keys = explode('.', $key);
                    $temp = &$result;

                    foreach ($keys as $i => $nestedKey) {
                        if (!isset($temp[$nestedKey]))
                            $temp[$nestedKey] = ((count($keys) - 1) == $i) ? $value : [];

                        $temp = &$temp[$nestedKey];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Sets the variable into the environment
     * @return void
     */
    protected function setVariable(array $variables)
    {
        // Merge variable to $_ENV
        $_ENV = array_merge($_ENV, $variables);

        // Merge variables to $_SERVER
        $_SERVER = array_merge($_SERVER, $variables);
    }
}
