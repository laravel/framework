<?php

namespace Illuminate\Image;

use Illuminate\Contracts\Image\Driver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Image\Drivers\GdDriver;
use Illuminate\Image\Drivers\ImagickDriver;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class ImageManager extends Manager
{
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
            fn () => $this->container->make(Filesystem::class)->get($path),
        );
    }

    /**
     * Create an image instance from a URL.
     */
    public function fromUrl(string $url): Image
    {
        return new Image(
            fn () => $this->container->make(HttpFactory::class)->get($url)->body(),
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
     * Create a new driver instance.
     *
     * @throws InvalidArgumentException
     */
    protected function createDriver($driver): Driver
    {
        try {
            $instance = parent::createDriver($driver);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("Image driver [{$driver}] is not supported.", 0, $e);
        }

        if (method_exists($instance, 'ensureRequirementsAreMet')) {
            $instance->ensureRequirementsAreMet();
        }

        return $instance;
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
     * Get the default image driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('image.default', 'gd');
    }
}
