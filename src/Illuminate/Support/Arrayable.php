<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Macroable;
use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Support\Traits\Conditionable;

class Arrayable
{
    use Conditionable, Macroable;

    /**
     * The underlying array value.
     *
     * @var array
     */
    protected $value;

    /**
     * Create a new instance of the class.
     *
     * @param  array  $value
     * @return void
     */
    public function __construct($value = [])
    {
        $this->value = $value;
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
     * Cross join the given arrays, returning all possible permutations.
     *
     * @param  iterable  ...$arrays
     * @return static
     */
    public function crossJoin(...$arrays)
    {
        return new static(Arr::crossJoin($this->value, $arrays));
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
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
        return new static(Arr::undot($this->value));
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
        return Arr::get($key, $default);
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
        return !$this->isAssoc();
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
        return new static(Collection::make($this->value)->keyBy($keyBy)->all());
    }

    /**
     * Prepend the key names of an associative array.
     *
     * @param  string  $prependWith
     * @return static
     */
    public function prependKeysWith($prependWith)
    {
        $array = Collection::make($this->value)->mapWithKeys(function ($item, $key) use ($prependWith) {
            return [$prependWith . $key => $item];
        })->all();

        return new static($array);
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
     * Push an item onto the beginning of an array.
     *
     * @param  mixed  $value
     * @param  mixed  $key
     * @return static
     */
    public function prepend($value, $key = null)
    {
        return new static(Arr::prepend($this->value, ...func_get_args()));
    }

    /**
     * Convert the array into a query string.
     *
     * @return string
     */
    public function query()
    {
        return http_build_query($this->value, '', '&', PHP_QUERY_RFC3986);
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
        return new static(Collection::make($this->value)->sortBy($callback)->all());
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
     * Conditionally compile classes from an array into a CSS class list.
     *
     * @return string
     */
    public function toCssClasses()
    {
        return Arr::toCssClasses($this->value);
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  callable  $callback
     * @return static
     */
    public function where(callable $callback)
    {
        return new static(Arr::where($this->value, $callback));
    }

    /**
     * Filter items where the value is not null.
     *
     * @return static
     */
    public function whereNotNull()
    {
        return new static(Arr::whereNotNull($this->value));
    }

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @return static
     */
    public function wrap()
    {
        return new static(Arr::wrap($this->value));
    }

    /**
     * Dump the array.
     *
     * @return $this
     */
    public function dump()
    {
        VarDumper::dump($this->value);

        return $this;
    }

    /**
     * Dump the array and end the script.
     *
     * @return never
     */
    public function dd()
    {
        $this->dump();

        exit(1);
    }

    /**
     * Get the underlying array value.
     *
     * @return array
     */
    public function value()
    {
        return $this->value;
    }
}
