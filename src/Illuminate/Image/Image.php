<?php

namespace Illuminate\Image;

use Closure;
use finfo;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Contracts\Image\Transformation;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Transformations\Blur;
use Illuminate\Image\Transformations\Contain;
use Illuminate\Image\Transformations\Cover;
use Illuminate\Image\Transformations\Crop;
use Illuminate\Image\Transformations\FlipHorizontally;
use Illuminate\Image\Transformations\FlipVertically;
use Illuminate\Image\Transformations\Grayscale;
use Illuminate\Image\Transformations\Orient;
use Illuminate\Image\Transformations\Resize;
use Illuminate\Image\Transformations\Rotate;
use Illuminate\Image\Transformations\Scale;
use Illuminate\Image\Transformations\Sharpen;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Stringable;
use Throwable;

class Image implements Stringable
{
    use Conditionable, Macroable;

    /**
     * The image processing pipeline.
     */
    protected ImagePipeline $pipeline;

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
        $this->pipeline = new ImagePipeline;
    }

    /**
     * Set the cover dimensions.
     *
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    public function cover(int $width, int $height): static
    {
        return $this->transform(new Cover(max(1, $width), max(1, $height)));
    }

    /**
     * Set the contain dimensions.
     *
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    public function contain(int $width, int $height, ?string $background = null): static
    {
        return $this->transform(new Contain(max(1, $width), max(1, $height), $background));
    }

    /**
     * Crop the image to the given dimensions and position.
     *
     * @param  int<1, max>  $width
     * @param  int<1, max>  $height
     */
    public function crop(int $width, int $height, int $x = 0, int $y = 0): static
    {
        return $this->transform(new Crop(max(1, $width), max(1, $height), $x, $y));
    }

    /**
     * Resize the image to the given dimensions.
     *
     * @param  int<1, max>|null  $width
     * @param  int<1, max>|null  $height
     */
    public function resize(?int $width = null, ?int $height = null): static
    {
        if ($width === null && $height === null) {
            throw new ImageException('At least one resize dimension must be specified.');
        }

        return $this->transform(new Resize(
            $width === null ? null : max(1, $width),
            $height === null ? null : max(1, $height),
        ));
    }

    /**
     * Rotate the image clockwise by the given angle.
     */
    public function rotate(float $angle, ?string $background = null): static
    {
        return $this->transform(new Rotate($angle, $background));
    }

    /**
     * Set the scale dimensions.
     *
     * @param  int<1, max>|null  $width
     * @param  int<1, max>|null  $height
     */
    public function scale(?int $width = null, ?int $height = null): static
    {
        if ($width === null && $height === null) {
            throw new ImageException('At least one scale dimension must be specified.');
        }

        return $this->transform(new Scale(
            $width === null ? null : max(1, $width),
            $height === null ? null : max(1, $height),
        ));
    }

    /**
     * Auto-orient the image based on EXIF data.
     */
    public function orient(): static
    {
        return $this->transform(new Orient);
    }

    /**
     * Apply a blur effect.
     *
     * @param  int<0, 100>  $amount
     */
    public function blur(int $amount = 5): static
    {
        return $this->transform(new Blur(max(0, min(100, $amount))));
    }

    /**
     * Convert the image to grayscale.
     */
    public function grayscale(): static
    {
        return $this->transform(new Grayscale);
    }

    /**
     * Sharpen the image.
     *
     * @param  int<0, 100>  $amount
     */
    public function sharpen(int $amount = 10): static
    {
        return $this->transform(new Sharpen(max(0, min(100, $amount))));
    }

    /**
     * Flip the image vertically.
     */
    public function flipVertically(): static
    {
        return $this->transform(new FlipVertically);
    }

    /**
     * Flip the image horizontally.
     */
    public function flipHorizontally(): static
    {
        return $this->transform(new FlipHorizontally);
    }

    /**
     * Flip the image vertically.
     */
    public function flip(): static
    {
        return $this->flipVertically();
    }

    /**
     * Flip the image horizontally.
     */
    public function flop(): static
    {
        return $this->flipHorizontally();
    }

    /**
     * Add a transformation to the image pipeline.
     */
    public function transform(Transformation $transformation): static
    {
        return $this->withClone(fn (Image $image) => $image->pipeline->add($transformation));
    }

    /**
     * Set the optimization options.
     *
     * @throws ImageException
     */
    public function optimize(string $format = 'webp', int $quality = ImageOutputOptions::DEFAULT_QUALITY): static
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
        return $this->withOutput(fn (ImageOutputOptions $output) => $output->quality = max(1, min(100, $quality)));
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

        return $this->withOutput(fn (ImageOutputOptions $output) => $output->format = $format);
    }

    /**
     * Store the processed image on a filesystem disk.
     *
     * @param  array<string, mixed>  $options
     */
    public function store(string $path = '', ?string $disk = null, array $options = []): string|false
    {
        return $this->storeAs($path, $this->hashName(), $disk, $options);
    }

    /**
     * Store the processed image on a filesystem disk with public visibility.
     *
     * @param  array<string, mixed>  $options
     */
    public function storePublicly(string $path = '', ?string $disk = null, array $options = []): string|false
    {
        $options['visibility'] = 'public';

        return $this->storeAs($path, $this->hashName(), $disk, $options);
    }

    /**
     * Store the processed image on a filesystem disk with a given name.
     *
     * @param  array<string, mixed>  $options
     */
    public function storeAs(string $path, ?string $name = null, ?string $disk = null, array $options = []): string|false
    {
        if (is_null($name)) {
            [$path, $name] = ['', $path];
        }

        $path = trim($path.'/'.$name, '/');

        $result = Container::getInstance()->make(FilesystemFactory::class)
            ->disk($disk)
            ->put($path, $this->toBytes(), $options);

        return $result ? $path : false;
    }

    /**
     * Store the processed image on a filesystem disk with public visibility and a given name.
     *
     * @param  array<string, mixed>  $options
     */
    public function storePubliclyAs(string $path, ?string $name = null, ?string $disk = null, array $options = []): string|false
    {
        if (is_null($name)) {
            [$path, $name] = ['', $path];
        }

        $options['visibility'] = 'public';

        return $this->storeAs($path, $name, $disk, $options);
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
     * Process the image and return the raw bytes.
     */
    public function toBytes(): string
    {
        if ($this->pipeline->hasChanges() && ! $this->processed) {
            try {
                $this->contents = $this->resolveDriver()->process(
                    value($this->contents), $this->pipeline
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
     * Get the MIME type of the processed image.
     */
    public function mimeType(): string
    {
        return once(function () {
            return (new finfo(FILEINFO_MIME_TYPE))->buffer($this->toBytes());
        });
    }

    /**
     * Get the dimensions of the processed image.
     *
     * @return array{0: int, 1: int}
     */
    public function dimensions(): array
    {
        return once(function () {
            $size = @getimagesizefromstring($this->toBytes());

            if ($size === false) {
                throw new ImageException('Unable to determine the dimensions of the image.');
            }

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
     * Set the driver to use for processing.
     */
    public function using(string $driver): static
    {
        $clone = $this->newClone();

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
     * Get the underlying uploaded file instance.
     */
    public function file(): ?UploadedFile
    {
        return $this->file;
    }

    /**
     * Create an immutable clone with copied options.
     */
    protected function newClone(): static
    {
        $clone = clone $this;

        $clone->pipeline = clone $this->pipeline;
        $clone->processed = false;

        return $clone;
    }

    /**
     * Create an immutable clone with updated output options.
     */
    protected function withOutput(Closure $callback): static
    {
        return $this->withClone(fn (Image $image) => $callback($image->pipeline->output));
    }

    /**
     * Create an immutable clone with the given callback applied.
     */
    protected function withClone(Closure $callback): static
    {
        $clone = $this->newClone();

        $callback($clone);

        return $clone;
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
}
