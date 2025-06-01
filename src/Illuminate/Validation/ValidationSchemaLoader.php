<?php

namespace Illuminate\Validation;

class ValidationSchemaLoader
{
    /**
     * Load validation schema from file path or return inline rules.
     */
    public static function loadSchema($schema)
    {
        if (is_array($schema)) {
            return $schema;
        }

        if (is_string($schema)) {
            // Check if it's an inline encoded schema
            if (str_starts_with($schema, 'inline:')) {
                $encoded = substr($schema, 7); // Remove 'inline:' prefix
                $decoded = base64_decode($encoded);
                $rules = json_decode($decoded, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException("Invalid inline schema encoding");
                }

                return $rules;
            }

            // Try to load from JSON file
            $filePath = static::resolveSchemaPath($schema);

            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $rules = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException("Invalid JSON in schema file: {$filePath}");
                }

                return $rules;
            }

            throw new \InvalidArgumentException("Schema file not found: {$filePath}");
        }

        throw new \InvalidArgumentException("Schema must be an array or string path");
    }

    /**
     * Resolve the full path to the schema file.
     */
    public static function resolveSchemaPath($path)
    {
        // If it's already an absolute path, return as-is
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Try different base paths
        $basePaths = [
            function_exists('base_path') ? base_path('resources/validation') : null,
            function_exists('base_path') ? base_path('resources/schemas') : null,
            function_exists('base_path') ? base_path('storage/validation') : null,
            getcwd() . '/resources/validation',
            getcwd() . '/resources/schemas',
        ];

        foreach ($basePaths as $basePath) {
            if (!$basePath) continue;

            $fullPath = $basePath . '/' . ltrim($path, '/');

            // Add .json extension if not present
            if (!str_ends_with($fullPath, '.json')) {
                $fullPath .= '.json';
            }

            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        // If no base path works, try relative to current directory
        $relativePath = ltrim($path, '/');
        if (!str_ends_with($relativePath, '.json')) {
            $relativePath .= '.json';
        }

        return $relativePath;
    }
}
