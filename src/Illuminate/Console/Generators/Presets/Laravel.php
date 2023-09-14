<?php

namespace Illuminate\Console\Generators\Presets;

use Illuminate\Contracts\Config\Repository as ConfigContract;

class Laravel extends Preset
{
    /**
     * Preset name.
     *
     * @return string
     */
    public function name()
    {
        return 'laravel';
    }

    /**
     * Get the path to the base working directory.
     *
     * @return string
     */
    public function basePath()
    {
        return $this->app->basePath();
    }

    /**
     * Get the path to the source directory.
     *
     * @return string
     */
    public function sourcePath()
    {
        return $this->app->basePath('app');
    }

    /**
     * Get the path to the testing directory.
     *
     * @return string
     */
    public function testingPath()
    {
        return $this->app->basePath('tests');
    }

    /**
     * Get the path to the resource directory.
     *
     * @return string
     */
    public function resourcePath()
    {
        return $this->app->resourcePath();
    }

    /**
     * Get the path to the view directory.
     *
     * @return string
     */
    public function viewPath()
    {
        return $this->app['config']['view.paths'][0] ?? $this->app->resourcePath('views');
    }

    /**
     * Get the path to the factory directory.
     *
     * @return string
     */
    public function factoryPath()
    {
        return $this->app->databasePath('factories');
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    public function migrationPath()
    {
        return $this->app->databasePath('migrations');
    }

    /**
     * Get the path to the seeder directory.
     *
     * @return string
     */
    public function seederPath(): string
    {
        if (is_dir($seederPath = $this->app->databasePath('seeds'))) {
            return $seederPath;
        }

        return $this->app->databasePath('seeders');
    }

    /**
     * Preset namespace.
     *
     * @return string
     */
    public function rootNamespace()
    {
        return $this->app->getNamespace();
    }

    /**
     * Command namespace.
     *
     * @return string
     */
    public function commandNamespace()
    {
        return "{$this->rootNamespace()}Console\Commands\\";
    }

    /**
     * Model namespace.
     *
     * @return string
     */
    public function modelNamespace()
    {
        return is_dir("{$this->sourcePath()}/Models") ? "{$this->rootNamespace()}Models\\" : $this->rootNamespace();
    }

    /**
     * Provider namespace.
     *
     * @return string
     */
    public function providerNamespace()
    {
        return "{$this->rootNamespace()}Providers\\";
    }

    /**
     * Testing namespace.
     *
     * @return string
     */
    public function testingNamespace()
    {
        return 'Tests\\';
    }

    /**
     * Database factory namespace.
     *
     * @return string
     */
    public function factoryNamespace()
    {
        return 'Database\Factories\\';
    }

    /**
     * Database seeder namespace.
     *
     * @return string
     */
    public function seederNamespace()
    {
        return 'Database\Seeders\\';
    }

    /**
     * Preset has custom stub path.
     *
     * @return bool
     */
    public function hasCustomStubPath()
    {
        return true;
    }
}
