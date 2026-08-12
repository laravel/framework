<?php

namespace Illuminate\Filesystem;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;

class ReadThroughFilesystemAdapter implements FilesystemAdapter
{
    /**
     * Create a new read-through filesystem adapter instance.
     */
    public function __construct(
        protected FilesystemOperator $primary,
        protected FilesystemOperator $fallback,
        protected bool $throwOnPromotionFailure = false,
        protected bool $copy = true,
    ) {
    }

    /**
     * Determine if a file exists.
     */
    public function fileExists(string $path): bool
    {
        return $this->primary->fileExists($path) || $this->fallback->fileExists($path);
    }

    /**
     * Determine if a directory exists.
     */
    public function directoryExists(string $path): bool
    {
        return $this->primary->directoryExists($path) || $this->fallback->directoryExists($path);
    }

    /**
     * Write a file to the primary filesystem.
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $this->primary->write($path, $contents, $config->toArray());
    }

    /**
     * Write a file stream to the primary filesystem.
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->primary->writeStream($path, $contents, $config->toArray());
    }

    /**
     * Read a file, promoting it from the fallback filesystem when necessary.
     */
    public function read(string $path): string
    {
        if ($this->primary->fileExists($path)) {
            return $this->primary->read($path);
        }

        $contents = $this->fallback->read($path);

        if (! $this->copy) {
            return $contents;
        }

        if ($this->primary->fileExists($path)) {
            return $this->primary->read($path);
        }

        try {
            $this->primary->write($path, $contents);
        } catch (FilesystemException $exception) {
            $this->handlePromotionFailure($path, $exception);
        }

        return $contents;
    }

    /**
     * Read a file stream, promoting it from the fallback filesystem when necessary.
     */
    public function readStream(string $path)
    {
        if ($this->primary->fileExists($path)) {
            return $this->primary->readStream($path);
        }

        if (! $this->copy) {
            return $this->fallback->readStream($path);
        }

        $source = $this->fallback->readStream($path);

        $temporary = fopen('php://temp', 'w+b');

        try {
            if ($temporary === false || stream_copy_to_stream($source, $temporary) === false) {
                throw UnableToReadFile::fromLocation($path);
            }
        } finally {
            fclose($source);
        }

        rewind($temporary);

        if ($this->primary->fileExists($path)) {
            fclose($temporary);

            return $this->primary->readStream($path);
        }

        try {
            $this->primary->writeStream($path, $temporary);
        } catch (FilesystemException $exception) {
            if ($this->throwOnPromotionFailure) {
                fclose($temporary);
            }

            $this->handlePromotionFailure($path, $exception);
        }

        rewind($temporary);

        return $temporary;
    }

    /**
     * Delete a file from the primary filesystem.
     */
    public function delete(string $path): void
    {
        $this->primary->delete($path);
    }

    /**
     * Delete a directory from the primary filesystem.
     */
    public function deleteDirectory(string $path): void
    {
        $this->primary->deleteDirectory($path);
    }

    /**
     * Create a directory on the primary filesystem.
     */
    public function createDirectory(string $path, Config $config): void
    {
        $this->primary->createDirectory($path, $config->toArray());
    }

    /**
     * Set a file's visibility on the primary filesystem.
     */
    public function setVisibility(string $path, string $visibility): void
    {
        $this->primary->setVisibility($path, $visibility);
    }

    /**
     * Retrieve a file's visibility.
     */
    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, visibility: $this->readerFor($path)->visibility($path));
    }

    /**
     * Retrieve a file's MIME type.
     */
    public function mimeType(string $path): FileAttributes
    {
        return new FileAttributes($path, mimeType: $this->readerFor($path)->mimeType($path));
    }

    /**
     * Retrieve a file's last modified time.
     */
    public function lastModified(string $path): FileAttributes
    {
        return new FileAttributes($path, lastModified: $this->readerFor($path)->lastModified($path));
    }

    /**
     * Retrieve a file's size.
     */
    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes($path, fileSize: $this->readerFor($path)->fileSize($path));
    }

    /**
     * List the contents of the primary filesystem.
     */
    public function listContents(string $path, bool $deep): iterable
    {
        return $this->primary->listContents($path, $deep);
    }

    /**
     * Move a file on the primary filesystem.
     */
    public function move(string $source, string $destination, Config $config): void
    {
        $this->primary->move($source, $destination, $config->toArray());
    }

    /**
     * Copy a file on the primary filesystem.
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        $this->primary->copy($source, $destination, $config->toArray());
    }

    /**
     * Get the filesystem that contains the given path.
     */
    protected function readerFor(string $path): FilesystemOperator
    {
        return $this->primary->fileExists($path) ? $this->primary : $this->fallback;
    }

    /**
     * Handle an exception encountered while promoting a file.
     */
    protected function handlePromotionFailure(string $path, FilesystemException $exception): void
    {
        if ($this->throwOnPromotionFailure) {
            throw UnableToReadFile::fromLocation($path, 'Unable to promote file to the primary filesystem.', $exception);
        }
    }
}
