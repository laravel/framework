<?php

namespace Illuminate\Support\Facades;

/**
 * @method static array all()
 * @method static bool has($key)
 * @method static mixed get($key, $default = null)
 * @method static void prepend($key, $value)
 * @method static void push($key, $value)
 * @method static void set($key, $value = null)
 *
 * @see \Illuminate\Config\Repository
 */
class Config extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'config';
    }
}
