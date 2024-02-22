<?php

namespace Illuminate\Database\Upsertions;

use Illuminate\Filesystem\Filesystem;

class UpsertionCreator
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Create a new upsertion creator instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @return void
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Create a new upsertion.
     *
     * @param  string  $name
     * @return string
     */
    public function create($name)
    {
        $stub = $this->getStub();

        $path = $this->getPath($name);

        $this->filesystem->ensureDirectoryExists(dirname($path));

        $this->filesystem->put($path, $stub);

        return $path;
    }

    /**
     * Get the upsertion stub file.
     *
     * @return string
     */
    protected function getStub()
    {
        $stub = "{$this->stubPath()}/upsertion.stub";

        return $this->filesystem->get($stub);
    }

    /**
     * Get the stubs folder path.
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__.'/stubs';
    }

    /**
     * Ensure that an upsertion with the given name doesn't already exist.
     *
     * @param  string  $name
     * @return bool
     */
    public function ensureUpsertionDoesntAlreadyExist($name)
    {
        return $this->filesystem->exists("{$this->getUpsertionsPath()}/{$name}.php");
    }

    /**
     * Get the full path to the upsertion.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        return "{$this->getUpsertionsPath()}/{$name}.php";
    }

    /**
     * Get the upsertions folder path
     *
     * @return string
     */
    protected function getUpsertionsPath()
    {
        return database_path('/upsertions');
    }
}
