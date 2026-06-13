<?php

namespace Illuminate\Support\Facades;

use Illuminate\Image\ImageManager;

/**
 * @method static \Illuminate\Contracts\Image\Driver driver(string|null $name = null)
 * @method static \Illuminate\Image\Image fromBytes(string $contents)
 * @method static \Illuminate\Image\Image fromPath(string $path)
 * @method static \Illuminate\Image\Image fromUrl(string $url)
 * @method static \Illuminate\Image\Image fromBase64(string $base64)
 * @method static string getDefaultDriver()
 * @method static \Illuminate\Image\ImageManager extend(string $driver, \Closure $callback)
 * @method static void pruneOrphaned(string|null $driver = null)
 *
 * @see ImageManager
 */
class Image extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'image';
    }
}
