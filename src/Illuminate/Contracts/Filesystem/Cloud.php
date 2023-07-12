<?php

namespace Illuminate\Contracts\Filesystem;

interface Cloud extends Filesystem
{
    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function url($path);

    /**
     * Determine if a file exists.
     * @param  string  $path
     * @return bool
     */
    public function fileExists(string $path);
}
