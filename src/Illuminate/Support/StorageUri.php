<?php

namespace Illuminate\Support;

use DateTimeInterface;
use Illuminate\Container\Container;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Casts\AsStorageUri;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageUri implements Arrayable, Castable, Jsonable, JsonSerializable, Stringable
{
    use Conditionable, Dumpable, Macroable, Tappable;

    /**
     * The URI scheme.
     *
     * @var string
     */
    public const SCHEME = 'storage';

    /**
     * The storage disk name.
     */
    protected ?string $disk;

    /**
     * The path within the disk.
     */
    protected string $path;

    /**
     * Create a new storage URI instance.
     */
    public function __construct(?string $disk, string $path)
    {
        $this->disk = $disk;
        $this->path = ltrim($path, '/');
    }

    /**
     * Create a new storage URI instance from a URI string.
     *
     * @throws \InvalidArgumentException
     */
    public static function of(string $uri): static
    {
        return static::parse($uri);
    }

    /**
     * Parse a storage URI string into a StorageUri instance.
     *
     * @throws \InvalidArgumentException
     */
    public static function parse(string $uri): static
    {
        $parsed = parse_url($uri);

        if ($parsed === false) {
            throw new InvalidArgumentException("Invalid storage URI: {$uri}");
        }

        $scheme = $parsed['scheme'] ?? null;

        if ($scheme !== static::SCHEME) {
            throw new InvalidArgumentException(
                "Invalid storage URI scheme [{$scheme}]. Expected [".static::SCHEME.'].'
            );
        }

        $disk = $parsed['host'] ?? null;
        $path = ltrim($parsed['path'] ?? '', '/');

        if ($path === '') {
            throw new InvalidArgumentException("Storage URI is missing a path: {$uri}");
        }

        return new static($disk, $path);
    }

    /**
     * Create a storage URI for the default disk.
     */
    public static function make(string $path): static
    {
        return new static(null, $path);
    }

    /**
     * Create a storage URI for a specific disk.
     */
    public static function onDisk(string $disk, string $path): static
    {
        return new static($disk, $path);
    }

    /**
     * Get the storage disk name.
     */
    public function disk(): ?string
    {
        return $this->disk;
    }

    /**
     * Get the path within the disk.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get the file's extension.
     */
    public function extension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file's directory name.
     */
    public function dirname(): string
    {
        $dirname = pathinfo($this->path, PATHINFO_DIRNAME);

        return $dirname === '.' ? '' : $dirname;
    }

    /**
     * Get the file's basename.
     */
    public function basename(): string
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }

    /**
     * Get the file's filename without extension.
     */
    public function filename(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Get a new storage URI with a different disk.
     */
    public function withDisk(?string $disk): static
    {
        return new static($disk, $this->path);
    }

    /**
     * Get a new storage URI with a different path.
     */
    public function withPath(string $path): static
    {
        return new static($this->disk, $path);
    }

    /**
     * Get the filesystem adapter for this URI's disk.
     */
    protected function storage()
    {
        return Container::getInstance()
            ->make(FilesystemFactory::class)
            ->disk($this->disk);
    }

    /**
     * Determine if the file exists.
     */
    public function exists(): bool
    {
        return $this->storage()->exists($this->path);
    }

    /**
     * Determine if the file is missing.
     */
    public function missing(): bool
    {
        return $this->storage()->missing($this->path);
    }

    /**
     * Get the file's contents.
     */
    public function get(): ?string
    {
        return $this->storage()->get($this->path);
    }

    /**
     * Get the file's contents as a decoded JSON array.
     */
    public function json(int $flags = 0): ?array
    {
        return $this->storage()->json($this->path, $flags);
    }

    /**
     * Get a read-stream for the file.
     *
     * @return resource|null
     */
    public function readStream()
    {
        return $this->storage()->readStream($this->path);
    }

    /**
     * Get the URL for the file.
     *
     * @return string
     */
    public function url(): string
    {
        return $this->storage()->url($this->path);
    }

    /**
     * Get a temporary URL for the file.
     *
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function temporaryUrl(DateTimeInterface $expiration, array $options = []): string
    {
        return $this->storage()->temporaryUrl($this->path, $expiration, $options);
    }

    /**
     * Get the file's size in bytes.
     *
     * @return int
     */
    public function size(): int
    {
        return $this->storage()->size($this->path);
    }

    /**
     * Get the file's MIME type.
     *
     * @return string|false
     */
    public function mimeType(): string|false
    {
        return $this->storage()->mimeType($this->path);
    }

    /**
     * Get the file's last modification time.
     *
     * @return int
     */
    public function lastModified(): int
    {
        return $this->storage()->lastModified($this->path);
    }

    /**
     * Get the full filesystem path.
     *
     * @return string
     */
    public function fullPath(): string
    {
        return $this->storage()->path($this->path);
    }

    /**
     * Get the file's visibility.
     *
     * @return string
     */
    public function visibility(): string
    {
        return $this->storage()->getVisibility($this->path);
    }

    /**
     * Delete the file.
     *
     * @return bool
     */
    public function delete(): bool
    {
        return $this->storage()->delete($this->path);
    }

    /**
     * Create a streamed response for the file.
     *
     * @param  string|null  $name
     * @param  array  $headers
     * @param  string  $disposition
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function response(?string $name = null, array $headers = [], string $disposition = 'inline'): StreamedResponse
    {
        return $this->storage()->response($this->path, $name, $headers, $disposition);
    }

    /**
     * Create a streamed download response for the file.
     *
     * @param  string|null  $name
     * @param  array  $headers
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(?string $name = null, array $headers = []): StreamedResponse
    {
        return $this->storage()->download($this->path, $name, $headers);
    }

    /**
     * Convert the storage URI to a mail attachment.
     *
     * @return \Illuminate\Mail\Attachment
     */
    public function toAttachment(): Attachment
    {
        return Attachment::fromStorageDisk($this->disk, $this->path);
    }

    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<static, string|static>
     */
    public static function castUsing(array $arguments)
    {
        return AsStorageUri::castUsing($arguments);
    }

    /**
     * Get the URI string representation.
     *
     * @return string
     */
    public function toUri(): string
    {
        $disk = $this->disk ?? '';

        return static::SCHEME.'://'.$disk.'/'.$this->path;
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->toUri();
    }

    /**
     * Get the string representation of the storage URI.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toUri();
    }
}
