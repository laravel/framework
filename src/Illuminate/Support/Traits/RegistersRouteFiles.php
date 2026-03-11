<?php

namespace Illuminate\Support\Traits;

use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

trait RegistersRouteFiles
{
    /**
     * Track resolved absolute paths across all ServiceProviders
     * to prevent the same file from being loaded more than once.
     *
     * @var array<string>
     */
    protected static array $registeredRouteFiles = [];

    /**
     * Load one or more route files into the application.
     *
     * Examples:
     *   $this->registerRouteFiles('api')
     *   $this->registerRouteFiles(['web', 'api', 'admin'])
     *   $this->registerRouteFiles('api/v2', [
     *       'middleware' => ['api', 'auth:sanctum'],
     *       'prefix'     => 'api/v2',
     *   ]);
     *
     * @param  string|array<string>  $files
     * @param  array<string, mixed>  $options
     * @return static
     *
     * @throws InvalidArgumentException
     */
    public function registerRouteFiles(string|array $files, array $options = []): static
    {
        foreach ((array) $files as $file) {
            $path = $this->resolveRouteFilePath($file);

            if (in_array($path, static::$registeredRouteFiles, true)) {
                continue;
            }

            static::$registeredRouteFiles[] = $path;

            Route::group($options, $path);
        }

        return $this;
    }

    /**
     * Resolve a route file path to a confirmed absolute path.
     *
     * @param  string  $file
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function resolveRouteFilePath(string $file): string
    {
        $file = str_replace('\\', '/', $file);

        if (! str_ends_with($file, '.php')) {
            $file .= '.php';
        }

        if ($this->isAbsolutePath($file) && is_file($file)) {
            return $file;
        }

        $relative = ltrim($file, '/');

        $fromRoutes = base_path('routes/' . $relative);
        if (is_file($fromRoutes)) {
            return $fromRoutes;
        }

        $fromBase = base_path($relative);
        if (is_file($fromBase)) {
            return $fromBase;
        }

        throw new InvalidArgumentException(
            "Route file [{$file}] could not be found. "
            . "Checked: [routes/{$relative}] and [{$relative}] relative to base_path()."
        );
    }

    /**
     * Determine whether the given path is absolute.
     * Handles Unix, Windows drive letters, and UNC paths.
     *
     * @param  string  $path
     * @return bool
     */
    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1
            || str_starts_with($path, '\\\\');
    }
}