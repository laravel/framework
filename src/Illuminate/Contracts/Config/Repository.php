<?php

namespace Illuminate\Contracts\Config;

interface Repository
{
    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key);

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all();

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value = null);

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value);

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value);

    /**
     * Get the specified configuration value typed as a string.
     * If the value isn't a string it should throw an exception.
     */
    public function string(string $key): string;

    /**
     * Get the specified configuration value typed as an array.
     * If the value isn't an array it should throw an exception.
     */
    public function array(string $key): array;

    /**
     * Get the specified configuration value typed as an integer.
     * If the value isn't an integer it should throw an exception.
     */
    public function integer(string $key): int;

    /**
     * Get the specified configuration value typed as a boolean.
     * If the value isn't a boolean it should throw an exception.
     */
    public function boolean(string $key): bool;

    /**
     * Get the specified configuration value typed as a float.
     * If the value isn't a float it should throw an exception.
     */
    public function float(string $key): float;
}
