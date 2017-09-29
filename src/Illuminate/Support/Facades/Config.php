<?php

namespace Illuminate\Support\Facades;

/**
 * @method static bool has(string $key) Determine if the given configuration value exists.
 * @method static mixed get(array | string $key, mixed $default) Get the specified configuration value.
 * @method static array getMany(array $keys) Get many configuration values.
 * @method static void set(array | string $key, mixed $value) Set a given configuration value.
 * @method static void prepend(string $key, mixed $value) Prepend a value onto an array configuration value.
 * @method static void push(string $key, mixed $value) Push a value onto an array configuration value.
 * @method static array all() Get all of the configuration items for the application.
 * @method static bool offsetExists(string $key) Determine if the given configuration option exists.
 * @method static mixed offsetGet(string $key) Get a configuration option.
 * @method static void offsetSet(string $key, mixed $value) Set a configuration option.
 * @method static void offsetUnset(string $key) Unset a configuration option.
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
