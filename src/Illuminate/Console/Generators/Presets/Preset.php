<?php

namespace Illuminate\Console\Generators\Presets;

use Illuminate\Contracts\Config\Repository as ConfigContract;

abstract class Preset
{
    /**
     * Construct a new preset.
     *
     * @param  string  $basePath
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(
        protected string $basePath,
        protected ConfigContract $config
    ) {
        //
    }

    /**
     * Check if preset name equal to $name.
     *
     * @param  string  $name
     * @return bool
     */
    public function is($name)
    {
        return $this->name() === $name;
    }

    /**
     * Get the path to the base working directory.
     *
     * @return string
     */
    public function basePath()
    {
        return $this->basePath;
    }

    /**
     * Get the path to the testing directory.
     *
     * @return string
     */
    public function testingPath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'tests']);
    }

    /**
     * Get the path to the vendor directory.
     *
     * @return string
     */
    public function vendorPath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'vendor']);
    }

    /**
     * Get the path to the resource directory.
     *
     * @return string
     */
    public function resourcePath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'resources']);
    }

    /**
     * Get the path to the view directory.
     *
     * @return string
     */
    public function viewPath()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->resourcePath(), 'views']);
    }

    /**
     * Get the path to the factory directory.
     */
    public function factoryPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'database', 'factories']);
    }

    /**
     * Get the path to the migration directory.
     */
    public function migrationPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'database', 'migrations']);
    }

    /**
     * Get the path to the seeder directory.
     *
     * @return string
     */
    public function seederPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->basePath(), 'database', 'seeders']);
    }

    /**
     * Database factory namespace.
     *
     * @return string
     */
    public function factoryNamespace(): string
    {
        return 'Database\Factories';
    }

    /**
     * Database seeder namespace.
     *
     * @return string
     */
    public function seederNamespace()
    {
        return 'Database\Seeders';
    }

    /**
     * Get the model for the default guard's user provider.
     *
     * @return string|null
     */
    public function userProviderModel()
    {
        $config = $this->config;

        $provider = $config->get('auth.guards.'.$config->get('auth.defaults.guard').'.provider');

        return $config->get("auth.providers.{$provider}.model");
    }

    /**
     * Preset has custom stub path.
     *
     * @return bool
     */
    public function hasCustomStubPath()
    {
        return ! is_null($this->getCustomStubPath());
    }

    /**
     * Preset name.
     *
     * @return string
     */
    abstract public function name();

    /**
     * Get the path to the base working directory.
     *
     * @return string
     */
    abstract public function laravelPath();

    /**
     * Get the path to the source directory.
     *
     * @return string
     */
    abstract public function sourcePath();

    /**
     * Preset namespace.
     *
     * @return string
     */
    abstract public function rootNamespace();

    /**
     * Testing namespace.
     *
     * @return string
     */
    abstract public function testingNamespace();

    /**
     * Model namespace.
     *
     * @return string
     */
    abstract public function modelNamespace();

    /**
     * Provider namespace.
     *
     * @return string
     */
    abstract public function providerNamespace();

    /**
     * Get custom stub path.
     *
     * @return string|null
     */
    abstract public function getCustomStubPath();
}
