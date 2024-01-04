<?php

namespace MVCME\Asset;

use MVCME\Controller;
use MVCME\Config\App;
use MVCME\Config\Assets;
use MVCME\Files\File;
use AppConfig\Assets as AssetsConfig;
use AppConfig\App as AppConfig;
use AppConfig\Mimes;

class AssetController extends Controller
{

    /**
     * Directory target when uploading
     * @var App
     */
    protected $config;

    /**
     * Base path of application assets
     * @var string
     */
    protected $basePath;

    /**
     * Directory target when uploading
     * @var string
     */
    protected $uploadPath;

    public function __construct(?Assets $assets = null, ?App $config = null)
    {
        if (empty($assets))
            $assets = new AssetsConfig;

        $this->basePath = $assets->assetPath . DIRECTORY_SEPARATOR;
        $this->uploadPath = $assets->uploadPath . DIRECTORY_SEPARATOR;
        $this->config = $config ?? new AppConfig;
    }


    /**
     * @return array
     * @internal
     */
    private function getIgnoredPath()
    {
        $assetHostname = $this->config->assetHostname;

        $ignoredPath = [];

        if (preg_match('~^/[A-Za-z_-]+$~', $assetHostname)) {
            $ignoredPath[] = ltrim($assetHostname, '/');
        }

        return $ignoredPath;
    }

    /**
     * Get full path of request assets
     * @return string
     */
    protected function getFullPath()
    {
        $paths = $this->request->getUri()->getSegments();
        $path = null;
        $ignorePath = $this->getIgnoredPath();

        foreach ($paths as $part) {
            if (in_array($part, $ignorePath))
                continue;

            $path = $path == null
                ? $part . DIRECTORY_SEPARATOR
                : rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $part;
        }

        return $path;
    }

    /**
     * @return bool
     */
    private function checkAsset(string $assetPath)
    {
        return file_exists($assetPath) && is_file($assetPath);
    }

    /**
     * Function to get file
     * @param string $path
     * @return object
     */
    private function getFile(string $path)
    {
        $file = new File($path);
        $extensions = explode('.', $file->getFilename());
        $extension = $extensions[count($extensions) - 1];

        $mime = new Mimes();
        $mimeType = $mime->guessTypeFromExtension($extension);

        $return = new \stdClass();
        $return->file = file_get_contents($path);
        $return->mimeType = $mimeType;
        return $return;
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $fullPath = $this->basePath . $this->getFullPath();

        if ($this->checkAsset($fullPath)) {

            $file = $this->getFile($fullPath);

            return $this->response
                ->setBody($file->file)
                ->setContentType($file->mimeType)
                ->setStatusCode(200);
        }

        return $this->response
            ->setBody("File not found")
            ->setStatusCode(404);
    }

    /**
     * @return string
     */
    public function error404()
    {
        return "No assets found";
    }
}
