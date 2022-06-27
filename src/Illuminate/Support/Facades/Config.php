<?php

namespace Illuminate\Support\Facades;

/**
 * @method static array all()
 * @method static mixed get(array|string $key, mixed $default = null)
 * @method static array getMany(array $keys)
 * @method static bool has(string $key)
 * @method static bool offsetExists(string $key)
 * @method static mixed offsetGet(string $key)
 * @method static void offsetSet(string $key, mixed $value)
 * @method static void offsetUnset(string $key)
 * @method static void prepend(string $key, mixed $value)
 * @method static void push(string $key, mixed $value)
 * @method static void set(array|string $key, mixed $value = null)
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
