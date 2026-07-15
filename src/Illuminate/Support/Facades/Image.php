<?php

namespace Illuminate\Support\Facades;

use Illuminate\Image\ImageManager;

/**
 * @method static \Illuminate\Image\Image fromBytes(string $contents)
 * @method static \Illuminate\Image\Image fromBase64(string $base64)
 * @method static \Illuminate\Image\Image fromPath(string $path)
 * @method static \Illuminate\Image\Image fromStorage(string $path, string|null $disk = null)
 * @method static \Illuminate\Image\Image fromUpload(\Illuminate\Http\UploadedFile $file)
 * @method static \Illuminate\Image\Image fromUrl(string $url)
 * @method static \Illuminate\Image\ImageManager transformUsing(string $driver, string $transformation, callable $callback)
 * @method static string getDefaultDriver()
 * @method static mixed driver(\UnitEnum|string|null $driver = null)
 * @method static \Illuminate\Image\ImageManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \Illuminate\Image\ImageManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \Illuminate\Image\ImageManager forgetDrivers()
 *
 * @see \Illuminate\Image\ImageManager
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
