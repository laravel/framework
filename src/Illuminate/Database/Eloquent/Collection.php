<?php namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection {

	/**
	 * A dictionary of available primary keys.
	 *
	 * @var array
	 */
	protected $dictionary = array();

	/**
	 * Find a model in the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $default
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function find($key, $default = null)
	{
		if (count($this->dictionary) == 0)
		{
			$this->buildDictionary();
		}

		return array_get($this->dictionary, $key, $default);
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

		// If the dictionary is empty, we will re-build it upon adding the item so
		// we can quickly search it from the "contains" method. This dictionary
		// will give us faster look-up times while searching for given items.
		if (count($this->dictionary) == 0)
		{
			$this->buildDictionary();
		}

		// If this dictionary has already been initially hydrated, we just need to
		// add an entry for the added item, which we will do here so that we'll
		// be able to quickly determine it is in the array when asked for it.
		elseif ($item instanceof Model)
		{
			$this->dictionary[$item->getKey()] = true;
		}

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
		if (count($this->dictionary) == 0)
		{
			$this->buildDictionary();
		}

		return isset($this->dictionary[$key]);
	}

	/**
	 * Build the dictionary of primary keys.
	 *
	 * @return void
	 */
	protected function buildDictionary()
	{
		$this->dictionary = array();

		// By building the dictionary of items by key, we are able to more quickly
		// access the array and examine it for certain items. This is useful on
		// the contain method which searches through the list by primary key.
		foreach ($this->items as $item)
		{
			if ($item instanceof Model)
			{
				$this->dictionary[$item->getKey()] = $item;
			}
		}
	}

}
