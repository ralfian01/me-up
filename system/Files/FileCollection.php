<?php

namespace MVCME\Files;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Class for access uploaded files from client request
 */
class FileCollection
{
    /**
     * An array of UploadedFile instances for any files
     * uploaded as part of this request.
     * Populated the first time either files(), file(), or hasFile()
     * is called.
     * @var array|null
     */
    protected $files;

    /**
     * Returns an array of all uploaded files that were found.
     * Each element in the array will be an instance of UploadedFile.
     * The key of each element will be the client filename.
     * @return array|null
     */
    public function getFiles()
    {
        $this->collectFile();

        return $this->files;
    }

    /**
     * Attempts to get a single file from the collection of uploaded files.
     * @return UploadedFile|null
     */
    public function getFile(string $name)
    {
        $this->collectFile();

        if ($this->hasFile($name)) {
            if (strpos($name, '.') !== false) {
                $name         = explode('.', $name);
                $uploadedFile = $this->getValueDotNotationSyntax($name, $this->files);

                return $uploadedFile instanceof UploadedFile ? $uploadedFile : null;
            }

            if (array_key_exists($name, $this->files)) {
                $uploadedFile = $this->files[$name];

                return $uploadedFile instanceof UploadedFile ? $uploadedFile : null;
            }
        }

        return null;
    }

    /**
     * Verify if a file exist in the collection of uploaded files and is have been uploaded with multiple option
     * @return array|null
     */
    public function getFileMultiple(string $name)
    {
        $this->collectFile();

        if ($this->hasFile($name)) {
            if (strpos($name, '.') !== false) {
                $name         = explode('.', $name);
                $uploadedFile = $this->getValueDotNotationSyntax($name, $this->files);

                return (is_array($uploadedFile) && ($uploadedFile[array_key_first($uploadedFile)] instanceof UploadedFile)) ?
                    $uploadedFile : null;
            }

            if (array_key_exists($name, $this->files)) {
                $uploadedFile = $this->files[$name];

                return (is_array($uploadedFile) && ($uploadedFile[array_key_first($uploadedFile)] instanceof UploadedFile)) ?
                    $uploadedFile : null;
            }
        }

        return null;
    }

    /**
     * Checks whether an uploaded file with name $fileID exists in this request.
     * @param string $fileID The name of the uploaded file (from the input)
     */
    public function hasFile(string $fileID): bool
    {
        $this->collectFile();

        if (strpos($fileID, '.') !== false) {
            $segments = explode('.', $fileID);

            $el = $this->files;

            foreach ($segments as $segment) {
                if (!array_key_exists($segment, $el)) {
                    return false;
                }

                $el = $el[$segment];
            }

            return true;
        }

        return isset($this->files[$fileID]);
    }

    /**
     * Taking information from the $_FILES array, it creates an instance
     * of UploadedFile for each one, saving the results to this->files.
     * @return void
     */
    protected function collectFile()
    {
        if (is_array($this->files)) {
            return;
        }

        $this->files = [];

        if (empty($_FILES)) {
            return;
        }

        $files = $this->fixFilesArray($_FILES);

        foreach ($files as $name => $file) {
            $this->files[$name] = $this->createFileObject($file);
        }
    }

    /**
     * Given a file array, will create UploadedFile instances. Will loop over an array and create objects for each.
     * @return UploadedFile|UploadedFile[]
     */
    protected function createFileObject(array $array)
    {
        if (!isset($array['name'])) {
            $output = [];

            foreach ($array as $key => $values) {
                if (!is_array($values)) {
                    continue;
                }

                $output[$key] = $this->createFileObject($values);
            }

            return $output;
        }

        return new UploadedFile(
            $array['tmp_name'] ?? null,
            $array['name'] ?? null,
            $array['type'] ?? null,
            $array['size'] ?? null,
            $array['error'] ?? null,
            $array['full_path'] ?? null
        );
    }

    /**
     * Reformats the odd $_FILES array into something much more like we would expect, with each object having its own array.
     */
    protected function fixFilesArray(array $data): array
    {
        $output = [];

        foreach ($data as $name => $array) {
            foreach ($array as $field => $value) {
                $pointer = &$output[$name];

                if (!is_array($value)) {
                    $pointer[$field] = $value;

                    continue;
                }

                $stack    = [&$pointer];
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveArrayIterator($value),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $key => $val) {
                    array_splice($stack, $iterator->getDepth() + 1);
                    $pointer = &$stack[count($stack) - 1];
                    $pointer = &$pointer[$key];
                    $stack[] = &$pointer;

                    if (!$iterator->getSubIterator()) {
                        $pointer[$field] = $val;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Navigate through an array looking for a particular index
     * @param array $index The index sequence we are navigating down
     * @param array $value The portion of the array to process
     * @return UploadedFile|null
     */
    protected function getValueDotNotationSyntax(array $index, array $value)
    {
        $currentIndex = array_shift($index);

        if (isset($currentIndex) && is_array($index) && $index && is_array($value[$currentIndex]) && $value[$currentIndex]) {
            return $this->getValueDotNotationSyntax($index, $value[$currentIndex]);
        }

        return $value[$currentIndex] ?? null;
    }
}
