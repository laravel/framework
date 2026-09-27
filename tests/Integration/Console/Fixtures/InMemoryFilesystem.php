<?php

namespace Illuminate\Tests\Integration\Console\Fixtures;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

class InMemoryFilesystem extends Filesystem
{
    /**
     * The file contents, keyed by path.
     *
     * @var array<string, string>
     */
    public $files = [];

    /**
     * The paths written through put(), in order.
     *
     * @var array<int, string>
     */
    public $writes = [];

    /**
     * The paths read through get(), in order.
     *
     * @var array<int, string>
     */
    public $reads = [];

    /**
     * Indicates if put() should report a failed write.
     *
     * @var bool
     */
    public $failWrites = false;

    /**
     * The paths deleted through delete(), in order.
     *
     * @var array<int, string>
     */
    public $deletes = [];

    public function exists($path)
    {
        return array_key_exists($path, $this->files);
    }

    public function get($path, $lock = false)
    {
        if (! $this->exists($path)) {
            throw new FileNotFoundException("File does not exist at path {$path}.");
        }

        $this->reads[] = $path;

        return $this->files[$path];
    }

    public function put($path, $contents, $lock = false)
    {
        if ($this->failWrites) {
            return false;
        }

        $this->writes[] = $path;
        $this->files[$path] = $contents;

        return strlen($contents);
    }

    public function delete($paths)
    {
        foreach ((array) $paths as $path) {
            $this->deletes[] = $path;
            unset($this->files[$path]);
        }

        return true;
    }
}
