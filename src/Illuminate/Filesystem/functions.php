<?php

namespace Illuminate\Filesystem;

if (! function_exists('Illuminate\Filesystem\join_paths')) {
    /**
     * Join the given paths together.
     *
     * @param  string  $basePath
     * @param  string  $paths
     * @return string
     */
    function join_paths(string $basePath, string ...$paths)
    {
        return $basePath.collect($paths)->reject(fn ($path) => empty($path))
                ->transform(fn ($path) => DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR))
                ->join('');
    }
}
