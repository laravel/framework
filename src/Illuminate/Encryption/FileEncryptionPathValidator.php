<?php

namespace Illuminate\Encryption;

use Illuminate\Contracts\Encryption\EncryptException;

class FileEncryptionPathValidator
{
    /**
     * Directories that should never be encrypted.
     *
     * @var array
     */
    protected array $restrictedDirectories = [
        'vendor',
        'app',
        'config',
        '.git',
        'node_modules',
        'bootstrap/cache',
    ];

    /**
     * File patterns that should never be encrypted.
     *
     * @var array
     */
    protected array $restrictedPatterns = [
        '/\.php$/',
        '/^artisan$/',
        '/^composer\.json$/',
        '/^composer\.lock$/',
        '/^package\.json$/',
        '/^package-lock\.json$/',
    ];

    /**
     * The base path for validation.
     *
     * @var string|null
     */
    protected ?string $basePath = null;

    /**
     * Create a new path validator instance.
     *
     * @param  string|null  $basePath
     */
    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath;
    }

    /**
     * Set the base path for validation.
     *
     * @param  string  $basePath
     * @return static
     */
    public function setBasePath(string $basePath): static
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Validate that a path is safe for encryption.
     *
     * @param  string  $path
     * @return void
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function validateForEncryption(string $path): void
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            throw new EncryptException("Path does not exist: {$path}");
        }

        // Validate within base path if set
        if ($this->basePath !== null) {
            $realBasePath = realpath($this->basePath);

            if ($realBasePath !== false && ! str_starts_with($realPath, $realBasePath.DIRECTORY_SEPARATOR) && $realPath !== $realBasePath) {
                throw new EncryptException('Cannot encrypt files outside the project root.');
            }
        }

        // Check if path is a symlink
        if (is_link($path)) {
            throw new EncryptException("Cannot encrypt symlinks: {$path}");
        }

        // Check if within restricted directories
        $this->validateNotInRestrictedDirectory($path, $realPath);

        // Check file patterns (only for files)
        if (is_file($realPath)) {
            $this->validateFilePattern($path);
        }
    }

    /**
     * Validate that a path is not within a restricted directory.
     *
     * @param  string  $path
     * @param  string  $realPath
     * @return void
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    protected function validateNotInRestrictedDirectory(string $path, string $realPath): void
    {
        if ($this->basePath === null) {
            return;
        }

        $realBasePath = realpath($this->basePath);

        if ($realBasePath === false) {
            return;
        }

        foreach ($this->restrictedDirectories as $dir) {
            $restrictedPath = $realBasePath.DIRECTORY_SEPARATOR.$dir;

            if (str_starts_with($realPath, $restrictedPath.DIRECTORY_SEPARATOR) || $realPath === $restrictedPath) {
                throw new EncryptException("Cannot encrypt files in restricted directory: {$dir}");
            }
        }
    }

    /**
     * Validate that a file does not match restricted patterns.
     *
     * @param  string  $path
     * @return void
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    protected function validateFilePattern(string $path): void
    {
        $filename = basename($path);

        foreach ($this->restrictedPatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                throw new EncryptException("Cannot encrypt files matching pattern: {$pattern}");
            }
        }
    }

    /**
     * Add a restricted directory.
     *
     * @param  string  $directory
     * @return static
     */
    public function addRestrictedDirectory(string $directory): static
    {
        $this->restrictedDirectories[] = $directory;

        return $this;
    }

    /**
     * Remove a restricted directory.
     *
     * @param  string  $directory
     * @return static
     */
    public function removeRestrictedDirectory(string $directory): static
    {
        $this->restrictedDirectories = array_filter(
            $this->restrictedDirectories,
            fn ($dir) => $dir !== $directory
        );

        return $this;
    }

    /**
     * Add a restricted file pattern.
     *
     * @param  string  $pattern
     * @return static
     */
    public function addRestrictedPattern(string $pattern): static
    {
        $this->restrictedPatterns[] = $pattern;

        return $this;
    }

    /**
     * Remove a restricted file pattern.
     *
     * @param  string  $pattern
     * @return static
     */
    public function removeRestrictedPattern(string $pattern): static
    {
        $this->restrictedPatterns = array_filter(
            $this->restrictedPatterns,
            fn ($p) => $p !== $pattern
        );

        return $this;
    }

    /**
     * Get the list of restricted directories.
     *
     * @return array
     */
    public function getRestrictedDirectories(): array
    {
        return $this->restrictedDirectories;
    }

    /**
     * Get the list of restricted patterns.
     *
     * @return array
     */
    public function getRestrictedPatterns(): array
    {
        return $this->restrictedPatterns;
    }

    /**
     * Check if a path is valid for encryption without throwing an exception.
     *
     * @param  string  $path
     * @return bool
     */
    public function isValid(string $path): bool
    {
        try {
            $this->validateForEncryption($path);

            return true;
        } catch (EncryptException) {
            return false;
        }
    }
}
