<?php

namespace Illuminate\Config;

use ArrayAccess;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class Repository implements ArrayAccess, ConfigContract
{
    use Macroable;

    /**
     * All of the configuration items.
     *
     * @var array<string,mixed>
     */
    protected $items = [];

    /**
     * Create a new configuration repository.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $key, $default);
    }

    /**
     * Get many configuration values.
     *
     * @param  array<string|int,mixed>  $keys
     * @return array<string,mixed>
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

    /**
     * Get the specified string configuration value.
     *
     * @param  (\Closure():(string|null))|string|null  $default
     *
     * @throws \InvalidArgumentException
     */
    public function string(string $key, $default = null): string
    {
        $value = $this->get($key, $default);

        if (! is_string($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a string, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified string configuration value.
     *
     * @param  (\Closure():(string|null))|string|null  $default
     * @return non-empty-string
     *
     * @throws \InvalidArgumentException
     */
    public function nonEmptyString(string $key, $default = null): string
    {
        $value = $this->string($key, $default);

        if (trim($value) === '') {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a non empty string, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified string configuration value.
     *
     * @param  (\Closure():(string|null))|string|null  $default
     * @return non-falsy-string
     *
     * @throws \InvalidArgumentException
     */
    public function nonFalsyString(string $key, $default = null): string
    {
        $value = $this->string($key, $default);

        if (! (bool) $value) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a non falsy string, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified string configuration value.
     *
     * @param  (\Closure():(string|null))|string|null  $default
     * @return lowercase-string
     *
     * @throws \InvalidArgumentException
     */
    public function lowerCaseString(string $key, $default = null): string
    {
        $value = $this->string($key, $default);

        if (strtolower($value) !== $value) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a lower case string, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified string configuration value.
     *
     * @param  (\Closure():(string|null))|string|null  $default
     * @return uppercase-string
     *
     * @throws \InvalidArgumentException
     */
    public function upperCaseString(string $key, $default = null): string
    {
        $value = $this->string($key, $default);

        if (strtoupper($value) !== $value) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a upper case string, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified integer configuration value.
     *
     * @param  (\Closure():(int|null))|int|null  $default
     *
     * @throws \InvalidArgumentException
     */
    public function integer(string $key, $default = null): int
    {
        $value = $this->get($key, $default);

        if (! is_int($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an integer, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified integer configuration value.
     *
     * @param  (\Closure():(int|null))|int|null  $default
     * @return positive-int
     *
     * @throws \InvalidArgumentException
     */
    public function positiveInteger(string $key, $default = null): int
    {
        $value = $this->integer($key, $default);

        if ($value <= 0) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an positive integer, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified integer configuration value.
     *
     * @param  (\Closure():(int|null))|int|null  $default
     * @return negative-int
     *
     * @throws \InvalidArgumentException
     */
    public function negativeInteger(string $key, $default = null): int
    {
        $value = $this->integer($key, $default);

        if ($value >= 0) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an negative integer, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified integer configuration value.
     *
     * @param  (\Closure():(int|null))|int|null  $default
     * @return non-positive-int
     *
     * @throws \InvalidArgumentException
     */
    public function nonPositiveInteger(string $key, $default = null): int
    {
        $value = $this->integer($key, $default);

        if ($value > 0) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an non positive integer, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified integer configuration value.
     *
     * @param  (\Closure():(int|null))|int|null  $default
     * @return non-negative-int
     *
     * @throws \InvalidArgumentException
     */
    public function nonNegativeInteger(string $key, $default = null): int
    {
        $value = $this->integer($key, $default);

        if ($value < 0) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an non negative integer, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified integer configuration value.
     *
     * @param  (\Closure():(int|null))|int|null  $default
     * @return non-zero-int
     *
     * @throws \InvalidArgumentException
     */
    public function nonZeroInteger(string $key, $default = null): int
    {
        $value = $this->integer($key, $default);

        if ($value === 0) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an non zero integer, %s given.', $key, $value)
            );
        }

        return $value;
    }

    /**
     * Get the specified float configuration value.
     *
     * @param  (\Closure():(float|null))|float|null  $default
     *
     * @throws \InvalidArgumentException
     */
    public function float(string $key, $default = null): float
    {
        $value = $this->get($key, $default);

        if (! is_float($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a float, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified boolean configuration value.
     *
     * @param  (\Closure():(bool|null))|bool|null  $default
     *
     * @throws \InvalidArgumentException
     */
    public function boolean(string $key, $default = null): bool
    {
        $value = $this->get($key, $default);

        if (! is_bool($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a boolean, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified array configuration value.
     *
     * @param  (\Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null  $default
     * @return array<array-key, mixed>
     *
     * @throws \InvalidArgumentException
     */
    public function array(string $key, $default = null): array
    {
        $value = $this->get($key, $default);

        if (! is_array($value)) {
            throw new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an array, %s given.', $key, gettype($value))
            );
        }

        return $value;
    }

    /**
     * Get the specified array configuration value as a collection.
     *
     * @param  (\Closure():(array<array-key, mixed>|null))|array<array-key, mixed>|null  $default
     * @return Collection<array-key, mixed>
     */
    public function collection(string $key, $default = null): Collection
    {
        return new Collection($this->array($key, $default));
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key, []);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $key
     */
    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     */
    public function offsetUnset($key): void
    {
        $this->set($key, null);
    }
}
