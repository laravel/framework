<?php

namespace Illuminate\View\Compilers;

use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

abstract class Compiler
{
    /**
     * The Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Get the cache path for the compiled views.
     *
     * @var string
     */
    protected $cachePath;

    /**
     * Get the base path of the views directory.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Create a new compiler instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $cachePath
     * @param  string  $basePath
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Filesystem $files, $cachePath, $basePath = null)
    {
        if (! $cachePath) {
            throw new InvalidArgumentException('Please provide a valid cache path.');
        }

        $this->files = $files;
        $this->cachePath = $cachePath;
        $this->basePath = $basePath;
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * @param  string  $path
     * @return string
     */
    public function getCompiledPath($path)
    {
        // The sha1 should not care about where the project is stored.
        // We just get the relative path from the base path.
        $path = str_replace_first($this->basePath, '', $path);

        return $this->cachePath.'/'.sha1($path).'.php';
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path)
    {
        $compiled = $this->getCompiledPath($path);

        // If the compiled file doesn't exist we will indicate that the view is expired
        // so that it can be re-compiled. Else, we will verify the last modification
        // of the views is less than the modification times of the compiled views.
        if (! $this->files->exists($compiled)) {
            return true;
        }

        $lastModified = $this->files->lastModified($path);

        return $lastModified >= $this->files->lastModified($compiled);
    }
}
