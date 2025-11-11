<?php

namespace Illuminate\Filesystem;

use InvalidArgumentException;

class PathValidator
{
    /**
     * Validate a path for security issues.
     *
     * @param  string  $path
     * @param  bool  $allowEmpty
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function validate(string $path, bool $allowEmpty = false): void
    {
        if (! $allowEmpty && empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty.');
        }

        // Check for path traversal attempts
        if (str_contains($path, '..')) {
            throw new InvalidArgumentException('Path traversal detected in path: ' . $path);
        }

        // Check for null bytes (can be used to bypass file extension checks)
        if (str_contains($path, "\0")) {
            throw new InvalidArgumentException('Null byte detected in path: ' . $path);
        }

        // Check for dangerous characters that could be used for command injection
        $dangerousChars = ['|', '&', ';', '`', '$', '(', ')', '<', '>'];
        foreach ($dangerousChars as $char) {
            if (str_contains($path, $char)) {
                throw new InvalidArgumentException('Dangerous character detected in path: ' . $path);
            }
        }
    }

    /**
     * Sanitize a filename by removing or replacing dangerous characters.
     *
     * @param  string  $filename
     * @return string
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove null bytes
        $filename = str_replace("\0", '', $filename);
        
        // Remove path traversal attempts
        $filename = str_replace(['../', '..\\', '../', '..\\'], '', $filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[|&;`$()<>]/', '', $filename);
        
        // Remove leading/trailing dots and spaces
        $filename = trim($filename, '. ');
        
        // Ensure filename is not empty after sanitization
        if (empty($filename)) {
            $filename = 'sanitized_file';
        }
        
        return $filename;
    }

    /**
     * Check if a path is within allowed boundaries.
     *
     * @param  string  $path
     * @param  string  $basePath
     * @return bool
     */
    public static function isWithinBasePath(string $path, string $basePath): bool
    {
        $realPath = realpath($path);
        $realBasePath = realpath($basePath);
        
        if ($realPath === false || $realBasePath === false) {
            return false;
        }
        
        return str_starts_with($realPath, $realBasePath);
    }
}
