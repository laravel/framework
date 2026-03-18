<?php

namespace Illuminate\Foundation\Image;

use Illuminate\Container\Container;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;

/**
 * @mixin UploadedFile
 */
class PendingImage
{
    /**
     * The image processing options.
     */
    protected PendingImageOptions $options;

    /**
     * The driver override.
     */
    protected ?string $driver = null;

    /**
     * Whether the image has been processed.
     */
    protected bool $processed = false;

    /**
     * Create a new pending image instance.
     */
    public function __construct(
        protected UploadedFile $file,
        protected Filesystem $filesystem,
    ) {
        $this->options = new PendingImageOptions;
    }

    /**
     * Set the cover dimensions.
     *
     * @return $this
     */
    public function cover(int $width, int $height): static
    {
        $this->options->coverWidth = $width;
        $this->options->coverHeight = $height;

        return $this;
    }

    /**
     * Set the optimization options.
     *
     * @return $this
     */
    public function optimize(string $format = 'webp', int $quality = 80): static
    {
        $this->options->format = $format;
        $this->options->quality = $quality;

        return $this;
    }

    /**
     * Set the driver to use for processing.
     *
     * @return $this
     */
    public function using(string $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Get the underlying uploaded file instance.
     */
    public function file(): UploadedFile
    {
        return $this->file;
    }

    /**
     * Resolve the image processing driver.
     */
    protected function resolveDriver(): Driver
    {
        $manager = Container::getInstance()->make('image');

        return $this->driver
            ? $manager->driver($this->driver)
            : $manager->driver();
    }

    /**
     * Process the image.
     *
     * @return $this
     */
    public function process(): static
    {
        if ($this->options->hasChanges() && ! $this->processed) {
            $bytes = $this->resolveDriver()->process(
                $this->filesystem->get($this->file->getRealPath()), $this->options
            );

            $this->filesystem->put($this->file->getRealPath(), $bytes);

            $this->processed = true;
        }

        return $this;
    }

    /**
     * Process the image if needed, then proxy method calls to the underlying file.
     */
    public function __call(string $method, array $parameters): mixed
    {
        $this->process();

        return $this->file->{$method}(...$parameters);
    }
}
