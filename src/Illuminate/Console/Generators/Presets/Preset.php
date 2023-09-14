<?php

namespace Illuminate\Console\Generators\Presets;

use Illuminate\Contracts\Foundation\Application;
use LogicException;

abstract class Preset
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Construct a new preset.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
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
     * Preset has custom stub path.
     *
     * @return bool
     */
    public function hasCustomStubPath()
    {
        return false;
    }

    /**
     * Get the path to the testing directory.
     *
     * @return string
     */
    public function testingPath()
    {
        return implode('/', [$this->basePath(), 'tests']);
    }

    /**
     * Get the path to the vendor directory.
     *
     * @return string
     */
    public function vendorPath()
    {
        return implode('/', [$this->basePath(), 'vendor']);
    }

    /**
     * Get the path to the resource directory.
     *
     * @return string
     */
    public function resourcePath()
    {
        return implode('/', [$this->basePath(), 'resources']);
    }

    /**
     * Get the path to the view directory.
     *
     * @return string
     */
    public function viewPath()
    {
        return implode('/', [$this->resourcePath(), 'views']);
    }

    /**
     * Get the path to the factory directory.
     *
     * @return string
     */
    public function factoryPath()
    {
        return implode('/', [$this->basePath(), 'database', 'factories']);
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    public function migrationPath()
    {
        return implode('/', [$this->basePath(), 'database', 'migrations']);
    }

    /**
     * Get the path to the seeder directory.
     *
     * @return string
     */
    public function seederPath()
    {
        return implode('/', [$this->basePath(), 'database', 'seeders']);
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
     * Get the model for the default guard's user provider.
     *
     * @param  string|null  $guard
     * @return string|null
     */
    public function userProviderModel($guard = null)
    {
        $config = $this->app['config'];

        $guard = $guard ?: $config->get('auth.defaults.guard');

        if (is_null($provider = $config->get('auth.guards.'.$guard.'.provider'))) {
            throw new LogicException('The ['.$guard.'] guard is not defined in your "auth" configuration file.');
        }

        return $config->get("auth.providers.{$provider}.model");
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
    abstract public function basePath();

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
     * Command namespace.
     *
     * @return string
     */
    abstract public function commandNamespace();

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
     * Testing namespace.
     *
     * @return string
     */
    abstract public function testingNamespace();
}
