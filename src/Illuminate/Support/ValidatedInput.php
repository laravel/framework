<?php

namespace Illuminate\Support;

use ArrayIterator;
use Illuminate\Contracts\Support\ValidatedData;
use Illuminate\Support\Facades\Date;
use stdClass;
use Symfony\Component\VarDumper\VarDumper;
use Traversable;

class ValidatedInput implements ValidatedData
{
    /**
     * The underlying input.
     *
     * @var array
     */
    protected $input;

    /**
     * Create a new validated input container.
     *
     * @param  array  $input
     * @return void
     */
    public function __construct(array $input)
    {
        $this->input = $input;
    }

    /**
     * Determine if the validated input has one or more keys.
     *
     * @param  mixed  $keys
     * @return bool
     */
    public function has($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (! Arr::has($this->all(), $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the validated input is missing one or more keys.
     *
     * @param  mixed  $keys
     * @return bool
     */
    public function missing($keys)
    {
        return ! $this->has($keys);
    }

    /**
     * Get a subset containing the provided keys with values from the input data.
     *
     * @param  mixed  $keys
     * @return array
     */
    public function only($keys)
    {
        $results = [];

        $input = $this->all();

        $placeholder = new stdClass;

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $value = data_get($input, $key, $placeholder);

            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    /**
     * Get all of the input except for a specified array of items.
     *
     * @param  mixed  $keys
     * @return array
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = $this->all();

        Arr::forget($results, $keys);

        return $results;
    }

    /**
     * Merge the validated input with the given array of additional data.
     *
     * @param  array  $items
     * @return static
     */
    public function merge(array $items)
    {
        return new static(array_merge($this->all(), $items));
    }

    /**
     * Get the input as a collection.
     *
     * @param  array|string|null  $key
     * @return \Illuminate\Support\Collection
     */
    public function collect($key = null)
    {
        return collect(is_array($key) ? $this->only($key) : $this->input($key));
    }

    /**
     * Get the raw, underlying input array.
     *
     * @return array
     */
    public function all()
    {
        return $this->input;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * Dynamically access input data.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->input($name);
    }

    /**
     * Dynamically set input data.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $this->input[$name] = $value;
    }

    /**
     * Determine if an input key is set.
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->exists($name);
    }

    /**
     * Remove an input key.
     *
     * @param  string  $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->input[$name]);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->exists($key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key): mixed
    {
        return $this->input($key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        if (is_null($key)) {
            $this->input[] = $value;
        } else {
            $this->input[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->input[$key]);
    }

    /**
     * Get an iterator for the input.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->input);
    }

    /**
     * Determine if the validated inputs contains a given input item key.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->has($key);
    }

    /**
     * Determine if the validated inputs contains any of the given inputs.
     *
     * @param  string|array  $keys
     * @return bool
     */
    public function hasAny($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $input = $this->all();

        return Arr::hasAny($input, $keys);
    }

    /**
     * Apply the callback if the validated inputs contains the given input item key.
     *
     * @param  string  $key
     * @param  callable  $callback
     * @param  callable|null  $default
     * @return $this|mixed
     */
    public function whenHas($key, callable $callback, ?callable $default = null)
    {
        if ($this->has($key)) {
            return $callback(data_get($this->all(), $key)) ?: $this;
        }

        if ($default) {
            return $default();
        }

        return $this;
    }

    /**
     * Determine if the validated inputs contains a non-empty value for an input item.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function filled($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the validated inputs contains an empty value for an input item.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function isNotFilled($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if (! $this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the validated inputs contains a non-empty value for any of the given inputs.
     *
     * @param  string|array  $keys
     * @return bool
     */
    public function anyFilled($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply the callback if the validated inputs contains a non-empty value for the given input item key.
     *
     * @param  string  $key
     * @param  callable  $callback
     * @param  callable|null  $default
     * @return $this|mixed
     */
    public function whenFilled($key, callable $callback, ?callable $default = null)
    {
        if ($this->filled($key)) {
            return $callback(data_get($this->all(), $key)) ?: $this;
        }

        if ($default) {
            return $default();
        }

        return $this;
    }

    /**
     * Apply the callback if the validated inputs is missing the given input item key.
     *
     * @param  string  $key
     * @param  callable  $callback
     * @param  callable|null  $default
     * @return $this|mixed
     */
    public function whenMissing($key, callable $callback, ?callable $default = null)
    {
        if ($this->missing($key)) {
            return $callback(data_get($this->all(), $key)) ?: $this;
        }

        if ($default) {
            return $default();
        }

        return $this;
    }

    /**
     * Determine if the given input key is an empty string for "filled".
     *
     * @param  string  $key
     * @return bool
     */
    protected function isEmptyString($key)
    {
        $value = $this->input($key);

        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }

    /**
     * Get the keys for all of the input.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->input());
    }

    /**
     * Retrieve an input item from the validated inputs.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        return data_get(
            $this->all(), $key, $default
        );
    }

    /**
     * Retrieve input from the validated inputs as a Stringable instance.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return \Illuminate\Support\Stringable
     */
    public function str($key, $default = null)
    {
        return $this->string($key, $default);
    }

    /**
     * Retrieve input from the validated inputs as a Stringable instance.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return \Illuminate\Support\Stringable
     */
    public function string($key, $default = null)
    {
        return str($this->input($key, $default));
    }

    /**
     * Retrieve input as a boolean value.
     *
     * Returns true when value is "1", "true", "on", and "yes". Otherwise, returns false.
     *
     * @param  string|null  $key
     * @param  bool  $default
     * @return bool
     */
    public function boolean($key = null, $default = false)
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Retrieve input as an integer value.
     *
     * @param  string  $key
     * @param  int  $default
     * @return int
     */
    public function integer($key, $default = 0)
    {
        return intval($this->input($key, $default));
    }

    /**
     * Retrieve input as a float value.
     *
     * @param  string  $key
     * @param  float  $default
     * @return float
     */
    public function float($key, $default = 0.0)
    {
        return floatval($this->input($key, $default));
    }

    /**
     * Retrieve input from the validated inputs as a Carbon instance.
     *
     * @param  string  $key
     * @param  string|null  $format
     * @param  string|null  $tz
     * @return \Illuminate\Support\Carbon|null
     *
     * @throws \Carbon\Exceptions\InvalidFormatException
     */
    public function date($key, $format = null, $tz = null)
    {
        if ($this->isNotFilled($key)) {
            return null;
        }

        if (is_null($format)) {
            return Date::parse($this->input($key), $tz);
        }

        return Date::createFromFormat($format, $this->input($key), $tz);
    }

    /**
     * Retrieve input from the validated inputs as an enum.
     *
     * @template TEnum
     *
     * @param  string  $key
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum|null
     */
    public function enum($key, $enumClass)
    {
        if ($this->isNotFilled($key) ||
            ! enum_exists($enumClass) ||
            ! method_exists($enumClass, 'tryFrom')) {
            return null;
        }

        return $enumClass::tryFrom($this->input($key));
    }

    /**
     * Dump the validated inputs items and end the script.
     *
     * @param  mixed  ...$keys
     * @return never
     */
    public function dd(...$keys)
    {
        $this->dump(...$keys);

        exit(1);
    }

    /**
     * Dump the items.
     *
     * @param  mixed  $keys
     * @return $this
     */
    public function dump($keys = [])
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        VarDumper::dump(count($keys) > 0 ? $this->only($keys) : $this->all());

        return $this;
    }
}
