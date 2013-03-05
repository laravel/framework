<?php namespace Illuminate\Support;

use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

class Collection implements ArrayAccess, ArrayableInterface, Countable, IteratorAggregate, JsonableInterface {

	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $items;

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
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->items;
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
	 * Execute a callback over each item.
	 *
	 * @param  Closure  $callback
	 * @return Illuminate\Support\Collection
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
		return array_map($callback, $this->items);
	}

	/**
	 * Run a filter over each of the items.
	 *
	 * @param  Closure  $callback
	 * @return Illuminate\Support\Collection
	 */
	public function filter(Closure $callback)
	{
		$this->items = array_filter($this->items, $callback);

		return $this;
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
			return $value->toArray();

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
		$this->items[$key] = $value;
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