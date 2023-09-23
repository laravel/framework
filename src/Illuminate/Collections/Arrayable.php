<?php

namespace Illuminate\Support;

use Symfony\Component\VarDumper\VarDumper;

class Arrayable
{
    /**
     * The underlying array value.
     *
     * @var array
     */
    protected $value;

    /**
     * Create a new instance of the class.
     *
     * @param  string|array  $values
     * @return void
     */
    public function __construct(string|array $values)
    {
        $this->value = $values;
    }

    /**
     * Determine whether the given value is array accessible.
     *
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
        $this->value = Arr::add($this->value, $key, $value);

        return $this;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  iterable  $array
     * @return static
     */
    public function collapse($array = [])
    {
        $this->value = Arr::collapse($this->value);

        if (! empty($array)) {
            $this->value = Arr::collapse([$this->value, ...$array]);
        }

        return $this;
    }

    /**
     * Cross join the given arrays, returning all possible permutations.
     *
     * @param  iterable  ...$arrays
     * @return static
     */
    public function crossJoin(...$arrays)
    {
        $this->value = Arr::crossJoin($this->value, ...$arrays);

        return $this;
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @return array
     */
    public function divide()
    {
        return Arr::divide($this->value);
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  string  $prepend
     * @return array
     */
    public function dot($prepend = '')
    {
        return Arr::dot($this->value, $prepend);
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param  array|string|int|float  $keys
     * @return array
     */
    public function except(...$keys)
    {
        $this->value = Arr::except($this->value, ...$keys);

        return $this;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  string|int  $key
     * @return bool
     */
    public function exists(string $key)
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
    public function first($callback = null, $default = null)
    {
        return Arr::first($this->value, $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  int  $depth
     * @return array
     */
    public function flatten($depth = INF)
    {
        $this->value = Arr::flatten($this->value, $depth);

        return $this;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array|string|int|float  $keys
     * @return void
     */
    public function forget($keys)
    {
        Arr::forget($this->value, $keys);

        return $this;
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
        return Arr::get($this->value, $key, $default = null);
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
     * @return array
     */
    public function keyBy($keyBy)
    {
        $this->value = Arr::keyBy($this->value, $keyBy);

        return $this;
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
     * Run a map over each of the items in the array.
     *
     * @param  callable  $callback
     * @return array
     */
    public function map(callable $callback)
    {
        $this->value = Arr::map($this->value, $callback);

        return $this;
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
     * @return array
     */
    public function mapWithKeys(callable $callback)
    {
        $this->value = Arr::mapWithKeys($this->value, $callback);

        return $this;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array|string  $keys
     * @return array
     */
    public function only($keys)
    {
        $this->value = Arr::only($this->value, $keys);

        return $this;
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  string|array|int|null  $value
     * @param  string|array|null  $key
     * @return array
     */
    public function pluck($value, $key = null)
    {
        $this->value = Arr::pluck($this->value, $value, $key);

        return $this;
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public function prepend($value, $key = null)
    {
        $this->value = Arr::prepend($this->value, $value, $key);

        return $this;
    }

    /**
     * Prepend the key names of an associative array.
     *
     * @param  string  $prependWith
     * @return array
     */
    public function prependKeysWith($prependWith)
    {
        $this->value = Arr::prependKeysWith($this->value, $prependWith);

        return $this;
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
        $this->{$key} = Arr::pull($this->value, $key, $default);

        Arr::forget($this->value, $key);

        return $this;
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
     * @return array
     */
    public function set($key, $value)
    {
        Arr::set($this->value, $key, $value);

        return $this;
    }

    /**
     * Shuffle the given array and return the result.
     *
     * @param  array  $array
     * @param  int|null  $seed
     * @return array
     */
    public function shuffle($seed = null)
    {
        $this->value = Arr::shuffle($this->value, $seed);

        return $this;
    }

    /**
     * Sort the array using the given callback or "dot" notation.
     *
     * @param  callable|array|string|null  $callback
     * @return array
     */
    public function sort($callback = null)
    {
        $this->value = Arr::sort($this->value, $callback);

        if (! is_null($callback)) {
            $this->value = array_values($this->value);
        }

        return $this;
    }

    /**
     * Sort the array in descending order using the given callback or "dot" notation.
     *
     * @param  callable|array|string|null  $callback
     * @return array
     */
    public function sortDesc($callback = null)
    {
        $this->value = Arr::sortDesc($this->value, $callback);

        if (! is_null($callback)) {
            $this->value = array_values($this->value);
        }

        return $this;
    }

    /**
     * Recursively sort an array by keys and values.
     *
     * @param  int  $options
     * @param  bool  $descending
     * @return array
     */
    public function sortRecursive($options = SORT_REGULAR, $descending = false)
    {
        $this->value = Arr::sortRecursive($this->value, $options = SORT_REGULAR, $descending = false);

        return $this;
    }

    /**
     * Recursively sort an array by keys and values in descending order.
     *
     * @param  int  $options
     * @return array
     */
    public function sortRecursiveDesc($options = SORT_REGULAR)
    {
        $this->value = Arr::sortRecursive($this->value, $options);

        return $this;
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
     * @param  array  $array
     * @return string
     */
    public function toCssStyles()
    {
        return Arr::toCssStyles($this->value);
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     *
     * @return array
     */
    public function undot()
    {
        $this->value = Arr::undot($this->value);

        return $this;
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  callable  $callback
     * @return array
     */
    public function where(callable $callback)
    {
        $this->value = Arr::where($this->value, $callback);

        return $this;
    }

    /**
     * Filter items where the value is not null.
     *
     * @return array
     */
    public function whereNotNull()
    {
        $this->value = Arr::whereNotNull($this->value);

        return $this;
    }

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param  mixed  $value
     * @return array
     */
    public function wrap()
    {
        $this->value = Arr::wrap($this->value);

        return $this;
    }

    /**
     * Dump the string.
     *
     * @return $this
     */
    public function dump()
    {
        VarDumper::dump($this->value);

        return $this;
    }

    /**
     * Dump the string and end the script.
     *
     * @return never
     */
    public function dd()
    {
        $this->dump();

        exit(1);
    }

    /**
     * Get the underlying string value.
     *
     * @return string
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Proxy dynamic properties onto methods.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->{$key}();
    }
}
