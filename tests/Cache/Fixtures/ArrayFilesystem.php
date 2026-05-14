<?php

namespace Illuminate\Tests\Cache\Fixtures;

use Illuminate\Contracts\Filesystem\Filesystem;

class ArrayFilesystem implements Filesystem
{
    public array $files = [];

    public function path($path)
    {
        return $path;
    }

    public function exists($path)
    {
        return array_key_exists($path, $this->files) || $this->files($path) !== [];
    }

    public function get($path)
    {
        return $this->files[$path] ?? null;
    }

    public function readStream($path)
    {
        return null;
    }

    public function put($path, $contents, $options = [])
    {
        $this->files[$path] = $contents;

        return true;
    }

    public function putFile($path, $file = null, $options = [])
    {
        return false;
    }

    public function putFileAs($path, $file, $name = null, $options = [])
    {
        return false;
    }

    public function writeStream($path, $resource, array $options = [])
    {
        return false;
    }

    public function getVisibility($path)
    {
        return Filesystem::VISIBILITY_PRIVATE;
    }

    public function setVisibility($path, $visibility)
    {
        return true;
    }

    public function prepend($path, $data)
    {
        return false;
    }

    public function append($path, $data)
    {
        return false;
    }

    public function delete($paths)
    {
        $deleted = false;

        foreach ((array) $paths as $path) {
            if (array_key_exists($path, $this->files)) {
                unset($this->files[$path]);

                $deleted = true;
            }
        }

        return $deleted;
    }

    public function copy($from, $to)
    {
        return false;
    }

    public function move($from, $to)
    {
        return false;
    }

    public function size($path)
    {
        return strlen($this->files[$path] ?? '');
    }

    public function lastModified($path)
    {
        return 0;
    }

    public function files($directory = null, $recursive = false)
    {
        $directory = trim((string) $directory, '/');

        return array_values(array_filter(array_keys($this->files), function ($path) use ($directory) {
            return $directory === '' || str_starts_with($path, $directory.'/');
        }));
    }

    public function allFiles($directory = null)
    {
        return $this->files($directory, true);
    }

    public function directories($directory = null, $recursive = false)
    {
        return [];
    }

    public function allDirectories($directory = null)
    {
        return [];
    }

    public function makeDirectory($path)
    {
        return true;
    }

    public function deleteDirectory($directory)
    {
        $deleted = false;

        foreach ($this->allFiles($directory) as $path) {
            unset($this->files[$path]);

            $deleted = true;
        }

        return $deleted;
    }
}
