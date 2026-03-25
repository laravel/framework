<?php

namespace Illuminate\Support\Facades;

use Illuminate\Foundation\Image\ImageManager;

/**
 * @method static \Illuminate\Contracts\Image\Driver driver(string|null $name = null)
 * @method static \Illuminate\Foundation\Image\Image fromBytes(string $contents)
 * @method static \Illuminate\Foundation\Image\Image fromPath(string $path)
 * @method static \Illuminate\Foundation\Image\Image fromUrl(string $url)
 * @method static \Illuminate\Foundation\Image\Image fromBase64(string $base64)
 * @method static string getDefaultDriver()
 * @method static \Illuminate\Foundation\Image\ImageManager extend(string $driver, \Closure $callback)
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
