<?php

namespace Illuminate\Support;

use ArrayAccess;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use JsonSerializable;
use Symfony\Component\VarDumper\VarDumper;

class Arrayable implements JsonSerializable, ArrayAccess
{
    use Conditionable, Macroable, Tappable;

    /**
     * The underlying array value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new instance of the class.
     *
     * @param  array  $value
     * @return void
     */
    public function __construct(mixed $value = [])
    {
        $this->value = $value;
    }

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function accessible()
    {
        return Arr::accessible($this->value);
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param  string|int|float  $key
     * @param  mixed  $value
     * @return static
     */
    public function add($key, $value)
    {
        return new static(Arr::add($this->value, $key, $value));
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @return static
     */
    public function collapse()
    {
        return new static(Arr::collapse($this->value));
    }

    /**
     * Cross join with the given arrays, returning all possible permutations.
     *
     * @param  mixed  ...$arrays
     * @return static
     */
    public function crossJoin(...$arrays)
    {
        return new static(Arr::crossJoin($this->value, ...$arrays));
    }

    /**
     * Divide an array into two arrays.  One with keys and the other with values.
     *
     * @return static
     */
    public function divide()
    {
        return new static(Arr::divide($this->value));
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  string  $prepend
     * @return static
     */
    public function dot($prepend = '')
    {
        return new static(Arr::dot($this->value, $prepend));
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     *
     * @return static
     */
    public function undot()
    {
        return new static (Arr::undot($this->value));
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param  array|string|int|float  $keys
     * @return static
     */
    public function except($keys)
    {
        return new static(Arr::except($this->value, $keys));
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  string|int  $key
     * @return bool
     */
    public function exists($key)
    {
        return Arr::exists($this->value, $key);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        return Arr::first($this->value, $callback, $default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        return Arr::last($this->value, $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  int  $depth
     * @return static
     */
    public function flatten($depth = INF)
    {
        return new static(Arr::flatten($this->value, $depth));
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array|string|int|float  $keys
     * @return static
     */
    public function forget($keys)
    {
        Arr::forget($this->value, $keys);

        return new static($this->value);
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->value, $key, $default);
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param  string|array  $keys
     * @return bool
     */
    public function has($keys)
    {
        return Arr::has($this->value, $keys);
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     *
     * @param  string|array  $keys
     * @return bool
     */
    public function hasAny($keys)
    {
        return Arr::hasAny($this->value, $keys);
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @return bool
     */
    public function isAssoc()
    {
        return Arr::isAssoc($this->value);
    }

    /**
     * Determines if an array is a list.
     *
     * An array is a "list" if all array keys are sequential integers starting from 0 with no gaps in between.
     *
     * @return bool
     */
    public function isList()
    {
        return Arr::isList($this->value);
    }

    /**
     * Join all items using a string. The final items can use a separate glue string.
     *
     * @param  string  $glue
     * @param  string  $finalGlue
     * @return string
     */
    public function join($glue, $finalGlue = '')
    {
        return Arr::join($this->value, $glue, $finalGlue);
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  callable|array|string  $keyBy
     * @return static
     */
    public function keyBy($keyBy)
    {
        return new static(Arr::keyBy($this->value, $keyBy));
    }

    /**
     * Prepend the key names of an associative array.
     *
     * @param  string  $prependWith
     * @return static
     */
    public function prependKeysWith($prependWith)
    {
        return new static(Arr::prependKeysWith($this->value, $prependWith));
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array|string  $keys
     * @return static
     */
    public function only($keys)
    {
        return new static(Arr::only($this->value, $keys));
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  string|array|int|null  $value
     * @param  string|array|null  $key
     * @return static
     */
    public function pluck($value, $key = null)
    {
        return new static(Arr::pluck($this->value, $value, $key));
    }

    /**
     * Run a map over each of the items in the array.
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback)
    {
        return new static(Arr::map($this->value, $callback));
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TKey
     * @template TValue
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return static
     */
    public function mapWithKeys(callable $callback)
    {
        return new static(Arr::mapWithKeys($this->value, $callback));
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  mixed  $value
     * @param  mixed  $key
     * @return static
     */
    public function prepend($value, $key = null)
    {
        return new static(Arr::prepend($this->value, $value, $key));
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  string|int  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->value, $key, $default);
    }

    /**
     * Convert the array into a query string.
     *
     * @return string
     */
    public function query()
    {
        return Arr::query($this->value);
    }

    /**
     * Get one or a specified number of random values from an array.
     *
     * @param  int|null  $number
     * @param  bool  $preserveKeys
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random($number = null, $preserveKeys = false)
    {
        return Arr::random($this->value, $number, $preserveKeys);
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  string|int|null  $key
     * @param  mixed  $value
     * @return static
     */
    public function set($key, $value)
    {
        Arr::set($this->value, $key, $value);

        return new static($this->value);
    }

    /**
     * Shuffle the given array and return the result.
     *
     * @param  int|null  $seed
     * @return static
     */
    public function shuffle($seed = null)
    {
        return new static(Arr::shuffle($this->value, $seed));
    }

    /**
     * Sort the array using the given callback or "dot" notation.
     *
     * @param  callable|array|string|null  $callback
     * @return static
     */
    public function sort($callback = null)
    {
        return new static(Arr::sort($this->value, $callback));
    }

    /**
     * Sort the array in descending order using the given callback or "dot" notation.
     *
     * @param  callable|array|string|null  $callback
     * @return static
     */
    public function sortDesc($callback = null)
    {
        return new static(Arr::sortDesc($this->value, $callback));
    }

    /**
     * Recursively sort an array by keys and values.
     *
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sortRecursive($options = SORT_REGULAR, $descending = false)
    {
        return new static(Arr::sortRecursive($this->value, $options, $descending));
    }

    /**
     * Recursively sort an array by keys and values in descending order.
     *
     * @param  int  $options
     * @return static
     */
    public function sortRecursiveDesc($options = SORT_REGULAR)
    {
        return new static(Arr::sortRecursiveDesc($this->value, $options));
    }

    /**
     * Conditionally compile classes from an array into a CSS class list.
     *
     * @return string
     */
    public function toCssClasses()
    {
        return Arr::toCssClasses($this->value);
    }

    /**
     * Conditionally compile styles from an array into a style list.
     *
     * @return string
     */
    public function toCssStyles()
    {
        return Arr::toCssStyles($this->value);
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  callable  $callback
     * @return static
     */
    public function where(callable $callback)
    {
        return new static(Arr::where($this->values, $callback));
    }

    /**
     * Filter items where the value is not null.
     *
     * @return array
     */
    public function whereNotNull()
    {
        return new static(Arr::whereNotNull($this->value));
    }

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param  mixed  $value
     * @return static
     */
    public function wrap()
    {
        return new static(Arr::wrap($this->value));
    }

    /**
     * Dump the underlying value.
     *
     * @return $this
     */
    public function dump()
    {
        VarDumper::dump($this->value);

        return $this;
    }

    /**
     * Dump the underlying value and end the script.
     *
     * @return never
     */
    public function dd()
    {
        $this->dump();

        exit(1);
    }

    /**
     * Get the underlying value.
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Convert the object to a string when JSON encoded.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    /**
     * Get the underlying value of this instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->toCollection()->toArray();
    }

    /**
     * Get the underlying value of this instance as a collection.
     *
     * @return Collection
     */
    public function toCollection()
    {
        return new Collection($this->value);
    }

    /**
     * Determine if the given value is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->value);
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->value[$offset]);
    }

    /**
     * Get the value at the given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->value[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->value[$offset] = $value;
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->value[$offset]);
    }

    /**
     * Proxy dynamic properties onto methods.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return new static($this->value[$key] ?? null);
    }

    /**
     * Get the raw string value.
     *
     * @return string
     */
    public function __toString()
    {
        return collect($this->value)->toJson();
    }
}
