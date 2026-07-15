<?php

namespace Illuminate\Image;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Drivers\GdDriver;
use Illuminate\Image\Drivers\ImagickDriver;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class ImageManager extends Manager
{
    /**
     * The registered transformation handlers.
     *
     * @var array<string, array<class-string<\Illuminate\Contracts\Image\Transformation>, callable>>
     */
    protected array $transformationHandlers = [];

    /**
     * Create an image instance from raw bytes.
     */
    public function fromBytes(string $contents): Image
    {
        return new Image($contents, origin: new ImageOrigin('bytes'));
    }

    /**
     * Create an image instance from a base64 encoded string.
     */
    public function fromBase64(string $base64): Image
    {
        return new Image(
            fn () => base64_decode($base64, true) ?: throw new ImageException('Invalid base64 image data.'),
            origin: new ImageOrigin('base64'),
        );
    }

    /**
     * Create an image instance from a file path.
     */
    public function fromPath(string $path): Image
    {
        return new Image(
            fn () => $this->container->make(Filesystem::class)->get($path),
            origin: new ImageOrigin('path', $path),
        );
    }

    /**
     * Create an image instance from a storage disk path.
     */
    public function fromStorage(string $path, ?string $disk = null): Image
    {
        return new Image(
            fn () => $this->container->make(FilesystemFactory::class)->disk($disk)->get($path),
            origin: new ImageOrigin('storage', $path, $disk),
        );
    }

    /**
     * Create an image instance from an uploaded file.
     */
    public function fromUpload(UploadedFile $file): Image
    {
        return new Image(fn () => $file->getContent(), $file, new ImageOrigin('upload', $file->getClientOriginalName()));
    }

    /**
     * Create an image instance from a URL.
     */
    public function fromUrl(string $url): Image
    {
        return new Image(
            fn () => $this->container->make(HttpFactory::class)->get($url)->body(),
            origin: new ImageOrigin('url', $url),
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

        $this->applyTransformationHandlers($driver, $instance);

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
     * Register a transformation handler for the given driver.
     *
     * @param  class-string<\Illuminate\Contracts\Image\Transformation>  $transformation
     */
    public function transformUsing(string $driver, string $transformation, callable $callback): static
    {
        $this->transformationHandlers[$driver][$transformation] = $callback;

        if (isset($this->drivers[$driver])) {
            $this->applyTransformationHandlers($driver, $this->drivers[$driver]);
        }

        return $this;
    }

    /**
     * Apply registered transformation handlers to the given driver instance.
     */
    protected function applyTransformationHandlers(string $driver, Driver $instance): void
    {
        foreach ($this->transformationHandlers[$driver] ?? [] as $transformation => $callback) {
            $instance->transformUsing($transformation, $callback);
        }
    }

    /**
     * Get the default image driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('image.default', 'gd');
    }
}
