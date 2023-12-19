<?php

namespace Illuminate\Filesystem;

if (! function_exists('Illuminate\Filesystem\join_paths')) {
    /**
     * Join the given paths together.
     *
     * @param  string  $basePath
     * @param  string  $path
     * @return string
     */
    function join_paths($basePath, $path = '')
    {
        return $basePath.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
