<?php namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection {

	/**
	 * Find a model in the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $default
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function find($key, $default = null)
	{
		return array_first($this->items, function($key, $model) use ($key)
		{
			return $model->getKey() == $key;

		}, $default);
	}

	/**
	 * Load a set of relationships onto the collection.
	 *
	 * @param  dynamic  string
	 * @return void
	 */
	public function load()
	{
		if (count($this->items) > 0)
		{
			$query = $this->first()->newQuery()->with(func_get_args());

			$this->items = $query->eagerLoadRelations($this->items);
		}
	}

	/**
	 * Add an item to the collection.
	 *
	 * @param  mixed  $item
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function add($item)
	{
		$this->items[] = $item;

		return $this;
	}

	/**
	 * Determine if a key exists in the collection.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function contains($key)
	{
		return ! is_null($this->find($key));
	}

	/**
	 * Get an array with the values of a given key.
	 *
	 * @param  string  $column
	 * @param  string  $key
	 * @return array
	 */
	public function lists($value, $key = null)
	{
		$results = array();

		foreach ($this->items as $item)
		{
			// If the key is "null", we will just append the value to the array and keep
			// looping. Otherwise we will key the array using the value of the key we
			// received from the developer. Then we'll return the final array form.
			if (is_null($key))
			{
				$results[] = $item->{$value};
			}
			else
			{
				$results[$item->{$key}] = $item->{$value};
			}
		}

		return $results;
	}

	/**
	 * Get the array of primary keys
	 *
	 * @return array
	 */
	public function modelKeys()
	{
		return array_map(function($m) { return $m->getKey(); }, $this->items);
	}

}
