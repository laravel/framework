<?php

namespace Illuminate\Foundation\Image;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Image\Drivers\CloudflareDriver;
use Illuminate\Foundation\Image\Drivers\GdDriver;
use Illuminate\Foundation\Image\Drivers\ImagickDriver;
use Illuminate\Http\Client\Factory as HttpFactory;
use InvalidArgumentException;

class ImageManager
{
    /**
     * The array of resolved drivers.
     *
     * @var array<string, Driver>
     */
    protected array $drivers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array<string, Closure>
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
            $driver = $this->callCustomCreator($name);
        } else {
            $driverMethod = 'create'.ucfirst($name).'Driver';

            if (! method_exists($this, $driverMethod)) {
                throw new InvalidArgumentException("Image driver [{$name}] is not supported.");
            }

            $driver = $this->{$driverMethod}();
        }

        if (method_exists($driver, 'ensureRequirementsAreMet')) {
            $driver->ensureRequirementsAreMet();
        }

        return $driver;
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
        return new GdDriver;
    }

    /**
     * Create the Imagick image driver.
     */
    protected function createImagickDriver(): ImagickDriver
    {
        return new ImagickDriver;
    }

    /**
     * Create the Cloudflare image driver.
     */
    protected function createCloudflareDriver(): CloudflareDriver
    {
        $config = $this->app['config']['image.drivers.cloudflare'] ?? [];

        return new CloudflareDriver(
            $this->app->make(HttpFactory::class),
            $config['account_id'] ?? '',
            $config['api_token'] ?? '',
            $config['prefix'] ?? '',
        );
    }

    /**
     * Create an image instance from raw bytes.
     */
    public function fromBytes(string $contents): Image
    {
        return new Image($contents);
    }

    /**
     * Create an image instance from a file path.
     */
    public function fromPath(string $path): Image
    {
        return new Image(
            fn () => $this->app->make(Filesystem::class)->get($path),
        );
    }

    /**
     * Create an image instance from a URL.
     */
    public function fromUrl(string $url): Image
    {
        return new Image(
            fn () => $this->app->make(HttpFactory::class)->get($url)->body(),
        );
    }

    /**
     * Create an image instance from a base64 encoded string.
     */
    public function fromBase64(string $base64): Image
    {
        return new Image(
            fn () => base64_decode($base64, true) ?: throw new ImageException('Invalid base64 image data.'),
        );
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
     * Prune orphaned images from the given driver.
     */
    public function pruneOrphaned(?string $name = null): void
    {
        $driver = $this->driver($name);

        if (method_exists($driver, 'pruneOrphaned')) {
            $driver->pruneOrphaned();
        }
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->driver()->$method(...$parameters);
    }
}
