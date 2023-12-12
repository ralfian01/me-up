<?php

namespace MVCME\Files;

use MVCME\Files\File;
use AppConfig\Mimes;
use ReturnTypeWillChange;

/**
 * Value object representing a single file uploaded through an
 * HTTP request. Used by the IncomingRequest class to
 * provide files.
 */
class UploadedFile extends File
{
    /**
     * The path to the temporary file
     * @var string
     */
    protected $path;

    /**
     * The webkit relative path of the file
     * @var string
     */
    protected $clientPath;

    /**
     * The original filename as provided by the client
     * @var string
     */
    protected $originalName;

    /**
     * The filename given to a file during a move
     * @var string
     */
    protected $name;

    /**
     * The type of file as provided by PHP
     * @var string
     */
    protected $originalMimeType;

    /**
     * The error constant of the upload
     * @var int
     */
    protected $error;

    /**
     * Whether the file has been moved already or not
     * @var bool
     */
    protected $hasMoved = false;

    /**
     * Accepts the file information as would be filled in from the $_FILES array.
     * @param string $path         The temporary location of the uploaded file.
     * @param string $originalName The client-provided filename.
     * @param string $mimeType     The type of file as provided by PHP
     * @param int    $size         The size of the file, in bytes
     * @param int    $error        The error constant of the upload (one of PHP's UPLOADERRXXX constants)
     * @param string $clientPath   The webkit relative path of the uploaded file.
     */
    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $size = null, ?int $error = null, ?string $clientPath = null)
    {
        $this->path             = $path;
        $this->name             = $originalName;
        $this->originalName     = $originalName;
        $this->originalMimeType = $mimeType;
        $this->size             = $size;
        $this->error            = $error;
        $this->clientPath       = $clientPath;

        parent::__construct($path, false);
    }

    /**
     * Move the uploaded file to a new location
     * @param string $targetPath Path to which to move the uploaded file.
     * @param string $name the name to rename the file to.
     * @param bool   $overwrite State for indicating whether to overwrite the previously generated file with the same name or not.
     * @return bool
     */
    public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
    {
        $targetPath = rtrim($targetPath, '/') . '/';
        $targetPath = $this->setPath($targetPath); // set the target path

        $name ??= $this->getName();
        $destination = $overwrite ? $targetPath . $name : $this->getDestination($targetPath . $name);

        move_uploaded_file($this->path, $destination);

        @chmod($targetPath, 0777 & ~umask());

        // Success, so store our new information
        $this->path = $targetPath;
        $this->name = basename($destination);

        return true;
    }

    /**
     * Create file target path if the set path does not exist
     * @return string
     */
    protected function setPath(string $path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
            // create the index.html file
            if (!is_file($path . 'index.html')) {
                $file = fopen($path . 'index.html', 'x+b');
                fclose($file);
            }
        }

        return $path;
    }

    /**
     * Retrieve the filename. This will typically be the filename sent
     * by the client, and should not be trusted. If the file has been
     * moved, this will return the final name of the moved file.
     *
     * @return string The filename sent by the client or null if none was provided.
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Gets the temporary filename where the file was uploaded to.
     */
    public function getTempName()
    {
        return $this->path;
    }

    /**
     * Overrides SPLFileInfo's to work with uploaded files, since the temp file that's been uploaded doesn't have an extension
     * @return string|null
     */
    #[ReturnTypeWillChange]
    public function getExtension()
    {
        return $this->guessExtension() ?: $this->getClientExtension();
    }

    /**
     * Attempts to determine the best file extension from the file's
     * mime type. In contrast to getExtension, this method will return
     * an empty string if it fails to determine an extension instead of
     * falling back to the unsecure clientExtension.
     * @return string|null
     */
    public function guessExtension()
    {
        return Mimes::guessExtensionFromType($this->getMimeType(), $this->getClientExtension()) ?? '';
    }

    /**
     * Returns the original file extension, based on the file name that
     * was uploaded. This is NOT a trusted source.
     * For a trusted version, use guessExtension() instead.
     * @return string
     */
    public function getClientExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION) ?? '';
    }

    /**
     * Returns whether the file was uploaded successfully, based on whether
     * it was uploaded via HTTP and has no errors.
     * @return bool
     */
    public function isValid()
    {
        return is_uploaded_file($this->path) && $this->error === UPLOAD_ERR_OK;
    }
}
