<?php

namespace Illuminate\Filesystem;

if (! function_exists('Illuminate\Filesystem\join_paths')) {
    /**
     * Join the given paths together.
     *
     * @param  string|null  $basePath
     * @param  string  ...$paths
     * @throws \InvalidArgumentException
     */
    function join_paths($basePath, ...$paths): string
    {
        foreach ($paths as $index => $path) {
            if (empty($path) && $path !== '0') {
                unset($paths[$index]);
            } else {
                // Prevent path traversal attacks
                if (str_contains($path, '..')) {
                    throw new \InvalidArgumentException('Path traversal detected in path: ' . $path);
                }

                $paths[$index] = DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $basePath.implode('', $paths);
    }
}
