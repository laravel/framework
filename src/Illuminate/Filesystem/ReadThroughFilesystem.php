<?php

namespace Illuminate\Filesystem;

use League\Flysystem\FilesystemAdapter as FlysystemAdapter;
use League\Flysystem\FilesystemOperator;

class ReadThroughFilesystem extends FilesystemAdapter
{
    /**
     * Create a new read-through filesystem instance.
     */
    public function __construct(
        FilesystemOperator $driver,
        FlysystemAdapter $adapter,
        array $config,
        protected FilesystemAdapter $primary,
        protected FilesystemAdapter $fallback,
    ) {
        parent::__construct($driver, $adapter, $config);
    }

    /**
     * Get the URL for the file at the given path.
     */
    public function url($path)
    {
        return $this->readerFor($path)->url($path);
    }

    /**
     * Get a temporary URL for the file at the given path.
     */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        return isset($this->temporaryUrlCallback)
            ? parent::temporaryUrl($path, $expiration, $options)
            : $this->readerFor($path)->temporaryUrl($path, $expiration, $options);
    }

    /**
     * Determine if temporary upload URLs can be generated.
     */
    public function providesTemporaryUploadUrls()
    {
        return isset($this->temporaryUploadUrlCallback) || $this->primary->providesTemporaryUploadUrls();
    }

    /**
     * Get a temporary upload URL for the file at the given path.
     */
    public function temporaryUploadUrl($path, $expiration, array $options = [])
    {
        return isset($this->temporaryUploadUrlCallback)
            ? parent::temporaryUploadUrl($path, $expiration, $options)
            : $this->primary->temporaryUploadUrl($path, $expiration, $options);
    }

    /**
     * Determine if temporary URLs can be generated.
     */
    public function providesTemporaryUrls()
    {
        return isset($this->temporaryUrlCallback)
            || $this->primary->providesTemporaryUrls()
            || $this->fallback->providesTemporaryUrls();
    }

    /**
     * Get the filesystem that contains the given path.
     */
    protected function readerFor($path)
    {
        return $this->primary->fileExists($path) ? $this->primary : $this->fallback;
    }
}
