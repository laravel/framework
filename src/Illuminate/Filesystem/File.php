<?php

namespace Illuminate\Filesystem;

use Illuminate\Container\Container;
use League\Flysystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;


class File
{

    /**
     * @var FilesystemAdapter
     */
    private $filesystem;

    /**
     * @var string
     */
    private $disk;

    /**
     * @var string
     */
    private $path;

    /**
     * File constructor.
     *
     * @param string      $path
     * @param string|null $disk
     *
     * @throws FileNotFoundException
     */
    public function __construct($path, $disk = null)
    {
        $this->disk = $disk ?? $this->getDefaultDisk();

        $this->filesystem = Container::getInstance()
                                     ->make(FilesystemFactory::class)
                                     ->disk($this->disk);

        if ( ! $this->filesystem->exists($path))
        {
            throw new FileNotFoundException($path);
        }

        $this->path = $path;
    }

    /**
     * Get the url.
     *
     * @return string
     */
    public function url()
    {
        return $this->filesystem->url($this->path);
    }

    /**
     * Delete the file.
     *
     * @return bool
     */
    public function delete()
    {
        return $this->filesystem->delete($this->path);
    }

    /**
     * Get the contents.
     *
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get()
    {
        return $this->filesystem->get($this->path);
    }

    /**
     * Copy a file to a new location.
     *
     * @param  string $to
     *
     * @return bool
     */
    public function copy($to)
    {
        return $this->filesystem->put($to, $this->get());
    }

    /**
     * Move a file to a new location.
     *
     * @param  string $to
     *
     * @return bool
     */
    public function move($to)
    {
        return $this->filesystem->move($this->path, $to);
    }

    /**
     * Get the file size of a given file.
     *
     * @return int
     */
    public function size()
    {
        return $this->filesystem->size($this->path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @return string|false
     */
    public function mimeType()
    {
        return $this->filesystem->mimeType($this->path);
    }

    /**
     * Get the file last modification time.
     *
     * @return int
     */
    public function lastModified()
    {
        return $this->filesystem->lastModified($this->path);
    }

    /**
     * Get the default disk name.
     *
     * @return string
     */
    public function getDefaultDisk()
    {
        return config('filesystems.default');
    }
}