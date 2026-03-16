<?php

namespace Illuminate\Foundation\Image;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Foundation\Image\Drivers\GdDriver;
use Illuminate\Foundation\Image\Drivers\ImagickDriver;
use InvalidArgumentException;
use Intervention\Image\ImageManager as InterventionImageManager;
use RuntimeException;

class ImageManager
{
    /**
     * The array of resolved drivers.
     */
    protected array $drivers = [];

    /**
     * The registered custom driver creators.
     */
    protected array $customCreators = [];

    /**
     * Create a new image manager instance.
     */
    public function __construct(protected Application $app)
    {
        //
    }

    /**
     * Get a driver instance.
     */
    public function driver(?string $name = null): Driver
    {
        $name = $name ?? $this->getDefaultDriver();

        return $this->drivers[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve the given driver.
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): Driver
    {
        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($name);
        }

        $driverMethod = 'create'.ucfirst($name).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}();
        }

        throw new InvalidArgumentException("Image driver [{$name}] is not supported.");
    }

    /**
     * Call a custom driver creator.
     */
    protected function callCustomCreator(string $name): Driver
    {
        return $this->customCreators[$name]($this->app);
    }

    /**
     * Create the GD image driver.
     */
    protected function createGdDriver(): GdDriver
    {
        $this->ensureRequirementsAreMet('gd');

        return new GdDriver;
    }

    /**
     * Create the Imagick image driver.
     */
    protected function createImagickDriver(): ImagickDriver
    {
        $this->ensureRequirementsAreMet('imagick');

        return new ImagickDriver;
    }

    /**
     * Ensure the requirements for the given driver are met.
     *
     * @throws RuntimeException
     */
    protected function ensureRequirementsAreMet(string $driver): void
    {
        if (! class_exists(InterventionImageManager::class)) {
            throw new RuntimeException(
                "Intervention Image is required to use the [{$driver}] driver. ".
                'You may require it via: [composer require intervention/image:^3.0].',
            );
        }
    }

    /**
     * Get the default image driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->app['config']['image.default'] ?? 'gd';
    }

    /**
     * Register a custom driver creator.
     *
     * @return $this
     */
    public function extend(string $driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}
