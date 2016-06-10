<?php

namespace Illuminate\View\Compilers;

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
     * Whether or not compiled views should be cached.
     *
     * @var bool
     */
    protected $shouldCache;

    /**
     * Create a new compiler instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $cachePath
     * @param  bool  $shouldCache
     * @return void
     */
    public function __construct(Filesystem $files, $cachePath, $shouldCache = true)
    {
        $this->files = $files;
        $this->cachePath = $cachePath;
        $this->shouldCache = $shouldCache;
    }

    /**
     * Get the path to the compiled version of a view.
     *
     * @param  string  $path
     * @return string
     */
    public function getCompiledPath($path)
    {
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
        // If the app is configured to not cache compiled views, return as expired.
        if (! $this->shouldCache) {
            return true;
        }

        $compiled = $this->getCompiledPath($path);

        // If the compiled file doesn't exist we will indicate that the view is expired
        // so that it can be re-compiled. Else, we will verify the last modification
        // of the views is less than the modification times of the compiled views.
        if (! $this->cachePath || ! $this->files->exists($compiled)) {
            return true;
        }

        $lastModified = $this->files->lastModified($path);

        return $lastModified >= $this->files->lastModified($compiled);
    }
}
