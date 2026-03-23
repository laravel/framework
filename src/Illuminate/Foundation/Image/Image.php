<?php

namespace Illuminate\Foundation\Image;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Image
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
     * The cached hash name.
     */
    protected ?string $hashName = null;

    /**
     * Create a new image instance.
     */
    public function __construct(
        protected Closure|string $contents,
        protected ?UploadedFile $file = null,
    ) {
        $this->options = new PendingImageOptions;
    }

    /**
     * Get the underlying uploaded file instance.
     */
    public function file(): ?UploadedFile
    {
        return $this->file;
    }

    /**
     * Set the cover dimensions.
     */
    public function cover(int $width, int $height): static
    {
        $clone = $this->cloneWith();

        $clone->options->coverWidth = $width;
        $clone->options->coverHeight = $height;

        return $clone;
    }

    /**
     * Set the scale dimensions.
     */
    public function scale(int $width, int $height): static
    {
        $clone = $this->cloneWith();

        $clone->options->scaleWidth = $width;
        $clone->options->scaleHeight = $height;

        return $clone;
    }

    /**
     * Auto-orient the image based on EXIF data.
     */
    public function orient(): static
    {
        $clone = $this->cloneWith();

        $clone->options->orient = true;

        return $clone;
    }

    /**
     * Apply a blur effect.
     */
    public function blur(int $amount = 5): static
    {
        $clone = $this->cloneWith();

        $clone->options->blur = $amount;

        return $clone;
    }

    /**
     * Convert the image to greyscale.
     */
    public function greyscale(): static
    {
        $clone = $this->cloneWith();

        $clone->options->greyscale = true;

        return $clone;
    }

    /**
     * Set the optimization options.
     *
     * @throws ImageException
     */
    public function optimize(string $format = 'webp', int $quality = 80): static
    {
        return $this->toFormat($format)->quality($quality);
    }

    /**
     * Set the output quality.
     */
    public function quality(int $quality): static
    {
        $clone = $this->cloneWith();

        $clone->options->quality = $quality;

        return $clone;
    }

    /**
     * Convert the image to WebP format.
     */
    public function toWebp(): static
    {
        return $this->toFormat('webp');
    }

    /**
     * Convert the image to JPEG format.
     */
    public function toJpg(): static
    {
        return $this->toFormat('jpg');
    }

    /**
     * Set the output format.
     *
     * @throws ImageException
     */
    protected function toFormat(string $format): static
    {
        if (! in_array($format, ['webp', 'jpg', 'jpeg'])) {
            throw new ImageException("The [{$format}] format is not supported.");
        }

        $clone = $this->cloneWith();

        $clone->options->format = $format;

        return $clone;
    }

    /**
     * Set the driver to use for processing.
     */
    public function using(string $driver): static
    {
        $clone = $this->cloneWith();

        $clone->driver = $driver;

        return $clone;
    }

    /**
     * Process the image and return the raw bytes.
     */
    public function toBytes(): string
    {
        if ($this->options->hasChanges() && ! $this->processed) {
            $this->contents = $this->resolveDriver()->process(
                value($this->contents), $this->options
            );

            $this->processed = true;
        }

        return value($this->contents);
    }

    /**
     * Store the processed image on a filesystem disk.
     */
    public function store(string $path = '', array|string $options = []): string|false
    {
        return $this->storeAs($path, $this->hashName(), $this->parseOptions($options));
    }

    /**
     * Store the processed image on a filesystem disk with public visibility.
     */
    public function storePublicly(string $path = '', array|string $options = []): string|false
    {
        $options = $this->parseOptions($options);

        $options['visibility'] = 'public';

        return $this->storeAs($path, $this->hashName(), $options);
    }

    /**
     * Store the processed image on a filesystem disk with a given name.
     */
    public function storeAs(string $path, ?string $name = null, array|string $options = []): string|false
    {
        if (is_null($name) || is_array($name)) {
            [$path, $name, $options] = ['', $path, $name ?? []];
        }

        $options = $this->parseOptions($options);

        $disk = Arr::pull($options, 'disk');

        return Container::getInstance()->make(FilesystemFactory::class)
            ->disk($disk)
            ->put(
                ($path ? $path.'/' : '').$name,
                $this->toBytes(),
                $options,
            );
    }

    /**
     * Store the processed image on a filesystem disk with public visibility and a given name.
     */
    public function storePubliclyAs(string $path, ?string $name = null, array|string $options = []): string|false
    {
        if (is_null($name) || is_array($name)) {
            [$path, $name, $options] = ['', $path, $name ?? []];
        }

        $options = $this->parseOptions($options);

        $options['visibility'] = 'public';

        return $this->storeAs($path, $name, $options);
    }

    /**
     * Get the MIME type of the processed image.
     */
    public function mimeType(): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->buffer($this->toBytes());
    }

    /**
     * Get the file extension based on the MIME type.
     */
    public function extension(): string
    {
        return match ($this->mimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/svg+xml' => 'svg',
            'image/tiff' => 'tiff',
            default => 'bin',
        };
    }

    /**
     * Get the dimensions of the processed image.
     *
     * @return array{0: int, 1: int}
     */
    public function dimensions(): array
    {
        $size = getimagesizefromstring($this->toBytes());

        return [$size[0], $size[1]];
    }

    /**
     * Get a hashed filename with the correct extension.
     */
    public function hashName(string $path = ''): string
    {
        $this->hashName ??= Str::random(40);

        $hash = $this->hashName.'.'.$this->extension();

        return $path ? $path.'/'.$hash : $hash;
    }

    /**
     * Parse the given options into an array.
     */
    protected function parseOptions(array|string $options): array
    {
        if (is_string($options)) {
            $options = ['disk' => $options];
        }

        return $options;
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
     * Create an immutable clone with copied options.
     */
    protected function cloneWith(): static
    {
        $clone = clone $this;

        $clone->options = clone $this->options;
        $clone->processed = false;

        return $clone;
    }
}
