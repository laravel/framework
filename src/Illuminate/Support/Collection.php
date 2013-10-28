<?php namespace Illuminate\Support;

use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use CachingIterator;
use IteratorAggregate;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

class Collection implements ArrayAccess, ArrayableInterface, Countable, IteratorAggregate, JsonableInterface {

	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Create a new collection.
	 *
	 * @param  array  $items
	 * @return void
	 */
	public function __construct(array $items = array())
	{
		$this->items = $items;
	}

	/**
	 * Create a new collection instance if the value isn't one already.
	 *
	 * @param  mixed  $items
	 * @return \Illuminate\Support\Collection
	 */
	public static function make($items)
	{
		if (is_null($items)) return new static;

		if ($items instanceof Collection) return $items;

		return new static(is_array($items) ? $items : array($items));
	}

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function has($key)
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Get an item from the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if (array_key_exists($key, $this->items))
		{
			return $this->items[$key];
		}

		return value($default);
	}

	/**
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Put an item in the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function put($key, $value)
	{
		$this->items[$key] = $value;
	}

	/**
	 * Get the first item from the collection.
	 *
	 * @return mixed|null
	 */
	public function first()
	{
		return count($this->items) > 0 ? reset($this->items) : null;
	}

	/**
	* Get the last item from the collection.
	*
	* @return mixed|null
	*/
	public function last()
	{
		return count($this->items) > 0 ? end($this->items) : null;
	}

	/**
	 * Get and remove the first item from the collection.
	 *
	 * @return mixed|null
	 */
	public function shift()
	{
		return array_shift($this->items);
	}

	/**
	 * Push an item onto the beginning of the collection.
	 *
	 * @param  mixed  $value
	 * @return void
	 */
	public function push($value)
	{
		array_unshift($this->items, $value);
	}

	/**
	 * Get and remove the last item from the collection.
	 *
	 * @return mixed|null
	 */
	public function pop()
	{
		return array_pop($this->items);
	}

	/**
	 * Remove an item from the collection by key.
	 *
	 * @param  mixed  $key
	 * @return void
	 */
	public function forget($key)
	{
		unset($this->items[$key]);
	}

	/**
	 * Reduce the collection to a single value.
	 *
	 * @param  callable  $callback
	 * @param  mixed  $initial
	 * @return mixed
	 */
	public function reduce($callback, $initial = null)
	{
		return array_reduce($this->items, $callback, $initial);
	}

	/**
	 * Execute a callback over each item.
	 *
	 * @param  Closure  $callback
	 * @return \Illuminate\Support\Collection
	 */
	public function each(Closure $callback)
	{
		array_map($callback, $this->items);

		return $this;
	}

	/**
	 * Run a map over each of the items.
	 *
	 * @param  Closure  $callback
	 * @return array
	 */
	public function map(Closure $callback)
	{
		return new static(array_map($callback, $this->items, array_keys($this->items)));
	}

	/**
	 * Run a filter over each of the items.
	 *
	 * @param  Closure  $callback
	 * @return \Illuminate\Support\Collection
	 */
	public function filter(Closure $callback)
	{
		return new static(array_filter($this->items, $callback));
	}

	/**
	 * Sort through each item with a callback.
	 *
	 * @param  Closure  $callback
	 * @return \Illuminate\Support\Collection
	 */
	public function sort(Closure $callback)
	{
		uasort($this->items, $callback);

		return $this;
	}

	/**
	 * Sort the collection using the given Closure.
	 *
	 * @param  \Closure  $callback
	 * @return \Illuminate\Support\Collection
	 */
	public function sortBy(Closure $callback)
	{
		$results = array();

		// First we will loop through the items and get the comparator from a callback
		// function which we were given. Then, we will sort the returned values and
		// and grab the corresponding values for the sorted keys from this array.
		foreach ($this->items as $key => $value)
		{
			$results[$key] = $callback($value);
		}

		asort($results);

		// Once we have sorted all of the keys in the array, we will loop through them
		// and grab the corresponding model so we can set the underlying items list
		// to the sorted version. Then we'll just return the collection instance.
		foreach (array_keys($results) as $key)
		{
			$results[$key] = $this->items[$key];
		}

		$this->items = $results;

		return $this;
	}

	/**
	 * Reverse items order.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function reverse()
	{
		return new static(array_reverse($this->items));
	}

	/**
	 * Reset the keys on the underlying array.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function values()
	{
		$this->items = array_values($this->items);

		return $this;
	}

	/**
	 * Fetch a nested element of the collection.
	 *
	 * @param  string  $key
	 * @return \Illuminate\Support\Collection
	 */
	public function fetch($key)
	{
		return new static(array_fetch($this->items, $key));
	}

	/**
	 * Get a flattened array of the items in the collection.
	 *
	 * @return array
	 */
	public function flatten()
	{
		return new static(array_flatten($this->items));
	}

	/**
	 * Collapse the collection items into a single array.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function collapse()
	{
		$results = array();

		foreach ($this->items as $values)
		{
			$results = array_merge($results, $values);
		}

		return new static($results);
	}

	/**
	 * Merge items with the collection items.
	 *
	 * @param  \Illuminate\Support\Collection|\Illuminate\Support\Contracts\ArrayableInterface|array  $items
	 * @return \Illuminate\Support\Collection
	 */
	public function merge($items)
	{
		if ($items instanceof Collection)
		{
			$items = $items->all();
		}
		elseif ($items instanceof ArrayableInterface)
		{
			$items = $items->toArray();
		}

		$results = array_merge($this->items, $items);

		return new static($results);
	}

	/**
	 * Slice the underlying collection array.
	 *
	 * @param  int   $offset
	 * @param  int   $length
	 * @param  bool  $preserveKeys
	 * @return \Illuminate\Support\Collection
	 */
	public function slice($offset, $length = null, $preserveKeys = false)
	{
		return new static(array_slice($this->items, $offset, $length, $preserveKeys));
	}

	/**
	 * Take the first or last {$limit} items.
	 *
	 * @param  int  $limit
	 * @return \Illuminate\Support\Collection
	 */
	public function take($limit = null)
	{
		if ($limit < 0) return $this->slice($limit, abs($limit));

		return $this->slice(0, $limit);
	}

	/**
	 * Get an array with the values of a given key.
	 *
	 * @param  string  $value
	 * @param  string  $key
	 * @return array
	 */
	public function lists($value, $key = null)
	{
		return array_pluck($this->items, $value, $key);
	}

	/**
	 * Concatenate values of a given key as a string.
	 *
	 * @param  string  $value
	 * @param  string  $glue
	 * @return string
	 */
	public function implode($value, $glue = null)
	{
		if (is_null($glue)) return implode($this->lists($value));

		return implode($glue, $this->lists($value));
	}

	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->items);
	}

	/**
	 * Get the collection of items as a plain array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array_map(function($value)
		{
			return $value instanceof ArrayableInterface ? $value->toArray() : $value;

		}, $this->items);
	}

	/**
	 * Get the collection of items as JSON.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Get a CachingIterator instance.
	 *
	 * @return \CachingIterator
	 */
	public function getCachingIterator($flags = CachingIterator::CALL_TOSTRING)
	{
		return new CachingIterator($this->getIterator(), $flags);
	}

	/**
	 * Count the number of items in the collection.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param  mixed  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->items[$key];
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		if (is_null($key))
		{
			$this->items[] = $value;
		}
		else
		{
			$this->items[$key] = $value;
		}
	}

	/**
	 * Unset the item at a given offset.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		unset($this->items[$key]);
	}

	/**
	 * Convert the collection to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

}
