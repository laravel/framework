<?php

namespace Illuminate\Filesystem;

use Illuminate\Contracts\Filesystem\Cloud as CloudFilesystemContract;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoragePath
{
    use Conditionable;
    use Dumpable;
    use Macroable {
        __call as macroCall;
    }
    use Tappable;

    protected string $path;

    protected FilesystemContract|CloudFilesystemContract|FilesystemAdapter $filesystem;

    public function __construct(string $path, FilesystemContract|CloudFilesystemContract|FilesystemAdapter $filesystem)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
    }

    /**
     * Determine if a file or directory exists.
     */
    public function exists(): bool
    {
        return $this->filesystem->exists($this->path);
    }

    /**
     * Determine if a file or directory is missing.
     */
    public function missing(): bool
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->missing($this->path);
        } else {
            return ! $this->filesystem->exists($this->path);
        }
    }

    /**
     * Get the full path to the file.
     */
    public function path(): string
    {
        return $this->filesystem->path($this->path);
    }

    /**
     * Get the contents of a file.
     */
    public function get(): ?string
    {
        return $this->filesystem->get($this->path);
    }

    /**
     * Get a resource to read the file.
     *
     * @return resource|null The path resource or null on failure.
     */
    public function readStream()
    {
        return $this->filesystem->readStream($this->path);
    }

    /**
     * Get the contents of a file as decoded JSON.
     */
    public function json(int $flags = 0): ?array
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->json($this->path, $flags);
        }

        // NOTE: Copied from FilesystemAdapter to support this method even on
        //       instanced of FilesystemContract|CloudFilesystemContract
        $content = $this->get();

        return is_null($content) ? null : json_decode($content, true, 512, $flags);
    }

    /**
     * Get the file size.
     */
    public function size(): int
    {
        return $this->filesystem->size($this->path);
    }

    /**
     * Get the file's last modification time.
     */
    public function lastModified(): int
    {
        return $this->filesystem->lastModified($this->path);
    }

    /**
     * Get the checksum for the file.
     */
    public function checksum(array $options = []): string|false
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->checksum($this->path, $options);
        }

        return false;
    }

    /**
     * Get the mime-type of the file.
     */
    public function mimeType(): string|false
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->mimeType($this->path);
        }

        return false;
    }

    /**
     * Write the contents of a file.
     *
     * @param  \Psr\Http\Message\StreamInterface|\Illuminate\Http\File|\Illuminate\Http\UploadedFile|string|resource  $contents
     * @param  mixed  $options
     */
    public function put($contents, $options = []): bool
    {
        return $this->filesystem->put($this->path, $contents, $options);
    }

    /**
     * Write a new file using a stream.
     *
     * @param  resource  $resource
     */
    public function writeStream($resource, array $options = []): bool
    {
        return $this->filesystem->writeStream($this->path, $resource, $options);
    }

    /**
     * Prepend to a file.
     */
    public function prepend(string $data, string $separator = PHP_EOL): bool
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->prepend($this->path, $data, $separator);
        } else {
            return $this->filesystem->prepend($this->path, $data);
        }
    }

    /**
     * Append to a file.
     */
    public function append(string $data, string $separator = PHP_EOL): bool
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->append($this->path, $data, $separator);
        } else {
            return $this->filesystem->append($this->path, $data);
        }
    }

    /**
     * Get the visibility for the path.
     */
    public function getVisibility(): string
    {
        return $this->filesystem->getVisibility($this->path);
    }

    /**
     * Set the visibility for the path.
     */
    public function setVisibility(string $visibility): bool
    {
        return $this->filesystem->setVisibility($this->path, $visibility);
    }

    /**
     * Delete the file at the path.
     */
    public function delete(): bool
    {
        return $this->filesystem->delete($this->path);
    }

    /**
     * Copy a file to a new location.
     */
    public function copy(string $destination): bool
    {
        return $this->filesystem->copy($this->path, $destination);
    }

    /**
     * Copy a file to a new location and return the new path instance.
     */
    public function copyTo(string $destination): ?static
    {
        if ($this->copy($destination)) {
            return new static($destination, $this->filesystem);
        }

        return null;
    }

    /**
     * Move a file to a new location.
     */
    public function move(string $destination): bool
    {
        return $this->filesystem->move($this->path, $destination);
    }

    /**
     * Move a file to a new location and return the new path instance.
     */
    public function moveTo(string $destination): ?static
    {
        if ($this->move($destination)) {
            return new static($destination, $this->filesystem);
        }

        return null;
    }

    /**
     * Get an array of all files in the directory.
     *
     * @return array<string>
     */
    public function files(bool $recursive = false): array
    {
        return $this->filesystem->files($this->path, $recursive);
    }

    /**
     * Get all of the files from the directory (recursive).
     *
     * @return array<string>
     */
    public function allFiles(): array
    {
        return $this->filesystem->allFiles($this->path);
    }

    /**
     * Get all of the directories within the directory.
     *
     * @return array<string>
     */
    public function directories(bool $recursive = false): array
    {
        return $this->filesystem->directories($this->path, $recursive);
    }

    /**
     * Get all (recursive) of the directories within the directory.
     *
     * @return array<string>
     */
    public function allDirectories(): array
    {
        return $this->filesystem->allDirectories($this->path);
    }

    /**
     * Create a directory.
     */
    public function makeDirectory(): bool
    {
        return $this->filesystem->makeDirectory($this->path);
    }

    /**
     * Recursively delete a directory.
     */
    public function deleteDirectory(): bool
    {
        return $this->filesystem->deleteDirectory($this->path);
    }

    /**
     * Get the URL for the file.
     */
    public function url(): string
    {
        if ($this->filesystem instanceof CloudFilesystemContract) {
            return $this->filesystem->url($this->path);
        }

        throw new RuntimeException('This driver does not support retrieving URLs.');
    }

    /**
     * Get a temporary URL for the file.
     *
     * @param  \DateTimeInterface  $expiration
     */
    public function temporaryUrl($expiration, array $options = []): string
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->temporaryUrl($this->path, $expiration, $options);
        }

        throw new RuntimeException('This driver does not support creating temporary URLs.');
    }

    /**
     * Create a streamed response for the file.
     */
    public function response(?string $name = null, array $headers = [], ?string $disposition = 'inline'): StreamedResponse
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->response($this->path, $name, $headers, $disposition);
        }

        throw new RuntimeException('This driver does not support responses.');
    }

    /**
     * Create a streamed download response for the file.
     */
    public function download(?string $name = null, array $headers = []): StreamedResponse
    {
        if ($this->filesystem instanceof FilesystemAdapter) {
            return $this->filesystem->download($this->path, $name, $headers);
        }

        throw new RuntimeException('This driver does not support downloads.');
    }

    /**
     * Get a new storage path instance for a nested path.
     */
    public function at(string $path): static
    {
        return new static(
            $this->path.'/'.ltrim($path, '/'),
            $this->filesystem
        );
    }

    /**
     * Get the path string.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the filesystem instance.
     */
    public function getFilesystem(): FilesystemContract|CloudFilesystemContract|FilesystemAdapter
    {
        return $this->filesystem;
    }

    /**
     * Dynamically call the filesystem instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->filesystem->$method($this->path, ...$parameters);
    }
}
