<?php

namespace Illuminate\Foundation\Image;

use Closure;
use finfo;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Stringable;
use Throwable;

class Image implements Stringable
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
     *
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    public function cover(int $width, int $height): static
    {
        $clone = $this->cloneWith();

        $clone->options->coverWidth = max(1, $width);
        $clone->options->coverHeight = max(1, $height);

        return $clone;
    }

    /**
     * Set the scale dimensions.
     *
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    public function scale(int $width, int $height): static
    {
        $clone = $this->cloneWith();

        $clone->options->scaleWidth = max(1, $width);
        $clone->options->scaleHeight = max(1, $height);

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
     *
     * @param  int<0, 100>  $amount
     */
    public function blur(int $amount = 5): static
    {
        $clone = $this->cloneWith();

        $clone->options->blur = max(0, min(100, $amount));

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
     * Sharpen the image.
     *
     * @param  int<0, 100>  $amount
     */
    public function sharpen(int $amount = 10): static
    {
        $clone = $this->cloneWith();

        $clone->options->sharpen = max(0, min(100, $amount));

        return $clone;
    }

    /**
     * Flip the image vertically.
     */
    public function flip(): static
    {
        $clone = $this->cloneWith();

        $clone->options->flip = true;

        return $clone;
    }

    /**
     * Flip the image horizontally.
     */
    public function flop(): static
    {
        $clone = $this->cloneWith();

        $clone->options->flop = true;

        return $clone;
    }

    /**
     * Set the optimization options.
     *
     * @throws ImageException
     */
    public function optimize(string $format = 'webp', int $quality = PendingImageOptions::DEFAULT_QUALITY): static
    {
        return $this->toFormat($format)->quality($quality);
    }

    /**
     * Set the output quality.
     *
     * @param  int<1, 100>  $quality
     */
    public function quality(int $quality): static
    {
        $clone = $this->cloneWith();

        $clone->options->quality = max(1, min(100, $quality));

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
     * Convert the image to JPEG format.
     */
    public function toJpeg(): static
    {
        return $this->toJpg();
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
     * Use the GD driver for processing.
     */
    public function usingGd(): static
    {
        return $this->using('gd');
    }

    /**
     * Use the Imagick driver for processing.
     */
    public function usingImagick(): static
    {
        return $this->using('imagick');
    }

    /**
     * Use the Cloudflare driver for processing.
     */
    public function usingCloudflare(): static
    {
        return $this->using('cloudflare');
    }

    /**
     * Process the image and return the raw bytes.
     */
    public function toBytes(): string
    {
        if ($this->options->hasChanges() && ! $this->processed) {
            try {
                $this->contents = $this->resolveDriver()->process(
                    value($this->contents), $this->options
                );
            } catch (ImageException $e) {
                throw $e;
            } catch (Throwable $e) {
                throw new ImageException("Failed to process image: {$e->getMessage()}", 0, $e);
            }

            $this->processed = true;
        }

        return value($this->contents);
    }

    /**
     * Process the image and return as a base64 encoded string.
     */
    public function toBase64(): string
    {
        return base64_encode($this->toBytes());
    }

    /**
     * Process the image and return as a data URI.
     */
    public function toDataUri(): string
    {
        return 'data:'.$this->mimeType().';base64,'.$this->toBase64();
    }

    /**
     * Store the processed image on a filesystem disk.
     *
     * @param  array<string, mixed>|string  $options
     */
    public function store(string $path = '', array|string $options = []): string|false
    {
        return $this->storeAs($path, $this->hashName(), $this->parseOptions($options));
    }

    /**
     * Store the processed image on a filesystem disk with public visibility.
     *
     * @param  array<string, mixed>|string  $options
     */
    public function storePublicly(string $path = '', array|string $options = []): string|false
    {
        $options = $this->parseOptions($options);

        $options['visibility'] = 'public';

        return $this->storeAs($path, $this->hashName(), $options);
    }

    /**
     * Store the processed image on a filesystem disk with a given name.
     *
     * @param  array<string, mixed>|string  $options
     */
    public function storeAs(string $path, ?string $name = null, array|string $options = []): string|false
    {
        if (is_null($name)) {
            [$path, $name, $options] = ['', $path, []];
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
     *
     * @param  array<string, mixed>|string  $options
     */
    public function storePubliclyAs(string $path, ?string $name = null, array|string $options = []): string|false
    {
        if (is_null($name)) {
            [$path, $name, $options] = ['', $path, []];
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
        return once(function () {
            return (new finfo(FILEINFO_MIME_TYPE))->buffer($this->toBytes());
        });
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
        return once(function () {
            $size = getimagesizefromstring($this->toBytes());

            return [$size[0], $size[1]];
        });
    }

    /**
     * Get the width of the processed image.
     */
    public function width(): int
    {
        return $this->dimensions()[0];
    }

    /**
     * Get the height of the processed image.
     */
    public function height(): int
    {
        return $this->dimensions()[1];
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
     *
     * @param  array<string, mixed>|string  $options
     * @return array<string, mixed>
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
     * Prevent serialization of the image.
     *
     *
     * @throws ImageException
     */
    public function __serialize(): never
    {
        throw new ImageException('Images cannot be serialized. Store the image first and serialize the path instead.');
    }

    /**
     * Get the string representation of the image.
     */
    public function toString(): string
    {
        return $this->toDataUri();
    }

    /**
     * Get the string representation of the image.
     */
    public function __toString(): string
    {
        return $this->toString();
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
