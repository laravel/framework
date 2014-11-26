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
		if ($key instanceof Model)
		{
			$key = $key->getKey();
		}

		return array_first($this->items, function($itemKey, $model) use ($key)
		{
			return $model->getKey() == $key;

		}, $default);
	}

	/**
	 * Load a set of relationships onto the collection.
	 *
	 * @param  mixed  $relations
	 * @return $this
	 */
	public function load($relations)
	{
		if (count($this->items) > 0)
		{
			if (is_string($relations)) $relations = func_get_args();

			$query = $this->first()->newQuery()->with($relations);

			$this->items = $query->eagerLoadRelations($this->items);
		}

		return $this;
	}

	/**
	 * Add an item to the collection.
	 *
	 * @param  mixed  $item
	 * @return $this
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
	 * Fetch a nested element of the collection.
	 *
	 * @param  string  $key
	 * @return static
	 */
	public function fetch($key)
	{
		return new static(array_fetch($this->toArray(), $key));
	}

	/**
	 * Get the max value of a given key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function max($key)
	{
		return $this->reduce(function($result, $item) use ($key)
		{
			return (is_null($result) || $item->{$key} > $result) ? $item->{$key} : $result;
		});
	}

	/**
	 * Get the min value of a given key.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function min($key)
	{
		return $this->reduce(function($result, $item) use ($key)
		{
			return (is_null($result) || $item->{$key} < $result) ? $item->{$key} : $result;
		});
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

	/**
	 * Merge the collection with the given items.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return static
	 */
	public function merge($items)
	{
		$dictionary = $this->getDictionary();

		foreach ($items as $item)
		{
			$dictionary[$item->getKey()] = $item;
		}

		return new static(array_values($dictionary));
	}

	/**
	 * Diff the collection with the given items.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return static
	 */
	public function diff($items)
	{
		$diff = new static;

		$dictionary = $this->getDictionary($items);

		foreach ($this->items as $item)
		{
			if ( ! isset($dictionary[$item->getKey()]))
			{
				$diff->add($item);
			}
		}

		return $diff;
	}

	/**
	 * Intersect the collection with the given items.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return static
	 */
	public function intersect($items)
	{
		$intersect = new static;

		$dictionary = $this->getDictionary($items);

		foreach ($this->items as $item)
		{
			if (isset($dictionary[$item->getKey()]))
			{
				$intersect->add($item);
			}
		}

		return $intersect;
	}

	/**
	 * Return only unique items from the collection.
	 *
	 * @return static
	 */
	public function unique()
	{
		$dictionary = $this->getDictionary();

		return new static(array_values($dictionary));
	}

	/**
	 * Returns only the models from the collection with the specified keys.
	 *
	 * @param  mixed  $keys
	 * @return static
	 */
	public function only($keys)
	{
		$dictionary = array_only($this->getDictionary(), $keys);

		return new static(array_values($dictionary));
	}

	/**
	 * Returns all models in the collection except the models with specified keys.
	 *
	 * @param  mixed  $keys
	 * @return static
	 */
	public function except($keys)
	{
		$dictionary = array_except($this->getDictionary(), $keys);

		return new static(array_values($dictionary));
	}

	/**
	 * Get a dictionary keyed by primary keys.
	 *
	 * @param  \ArrayAccess|array  $items
	 * @return array
	 */
	public function getDictionary($items = null)
	{
		$items = is_null($items) ? $this->items : $items;

		$dictionary = array();

		foreach ($items as $value)
		{
			$dictionary[$value->getKey()] = $value;
		}

		return $dictionary;
	}

	/**
	 * Get a base Support collection instance from this collection.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function toBase()
	{
		return new BaseCollection($this->items);
	}
	/**
	 * Get an array with the attribute values of given key.
	 *
	 * @param  string $key
	 * @return array
	 */
	public function listsAttributeArray($key)
	{
		$items = array();

		$first = $this->first();

		// We want to avoid the model loading
		// unloaded relationships separately.
		// Therefore we check if we're trying to list
		// a relationship and if it's already loaded.
		// If it is not the case, we'll load the relationship.
		if ($first instanceof Model)
		{
			if (method_exists($first, $key))
			{
				if ( ! array_key_exists($key, $first->relations))
				{
					$this->load($key);
				}
			}
		}

		// list the attribute.
		foreach ($this->items as $item)
		{
			$items[] = $item->getAttribute($key);
		}

		return $items;
	}

	/**
	 * Get a collection with the values of a given key.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function listsAttribute($key)
	{
		$items = $this->listsAttributeArray($key);

		$result = $this->newCollection($items);

		return $result;
	}

	/**
	 * Collapse the collection items into a single array collection,
	 * making use of the newCollection type defined by the first model.
	 *
	 * @return mixed
	 */
	public function collapse()
	{
		$results = array();

		foreach ($this->items as $values)
		{
			if ($values instanceof Collection)
			{
				$values = $values->all();
			}

			$results = array_merge($results, $values);
		}

		$result = $this->newCollection($results);

		return $result;
	}

	/**
	 * Return a collection of the type specified
	 * by the type of attribute we're collecting.
	 *
	 * @param  array $items
	 * @return mixed
	 */
	public function newCollection(array $items = null)
	{
		if (is_null($items))
		{
			$items = $this->items;
		}

		if (($first = reset($items)) instanceof Model)
		{
			$result = $first->newCollection($items);
		}
		else
		{
			$result = new Collection($items);
		}

		return $result;
	}

	/**
	 * Dynamically retrieve attributes on the models.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->listsAttribute($key);
	}
}
