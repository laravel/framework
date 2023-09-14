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
     * Get the path to the testing directory.
     *
     * @return string
     */
    abstract public function testingPath();

    /**
     * Get the path to the resource directory.
     *
     * @return string
     */
    abstract public function resourcePath();

    /**
     * Get the path to the view directory.
     *
     * @return string
     */
    abstract public function viewPath();

    /**
     * Get the path to the factory directory.
     *
     * @return string
     */
    abstract function factoryPath();

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    abstract public function migrationPath();

    /**
     * Get the path to the seeder directory.
     *
     * @return string
     */
    abstract public function seederPath();

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

    /**
     * Database factory namespace.
     *
     * @return string
     */
    abstract public function factoryNamespace();

    /**
     * Database seeder namespace.
     *
     * @return string
     */
    abstract public function seederNamespace();

    /**
     * Preset has custom stub path.
     *
     * @return bool
     */
    abstract public function hasCustomStubPath();
}
