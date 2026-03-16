<?php

namespace Illuminate\Support\Facades;

use Illuminate\Foundation\Image\ImageManager;

/**
 * @method static \Illuminate\Contracts\Image\Driver driver(string|null $name = null)
 * @method static string getDefaultDriver()
 * @method static \Illuminate\Foundation\Image\ImageManager extend(string $driver, \Closure $callback)
 *
 * @see ImageManager
 */
class Image extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'image';
    }
}
