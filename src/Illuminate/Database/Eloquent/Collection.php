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
	 * Get the array of primary keys
	 *
	 * @return array
	 */
	public function modelKeys()
	{
		return array_map(function($m) { return $m->getKey(); }, $this->items);
	}
	
	/**
	 * Get an array of property values
     * 
     * @param String|Array a model attribute, or list
	 * 
     * @example $usernames = $users->modelValues('usernames');
     * @example $fullnames = $users->modelValues('firstname', 'surname');
     * @example $fullnames = $users->modelValues(array('firstname', 'surname'));
     * 
	 * @return array
	 */
	public function modelValues($key)
	{
        $args = func_get_args();
        if ( count($args) > 1 ) {
            $key = $args;
        }
		return array_map(function($m) use ($key) {
            if ( is_array($key) ) {
                return array_map(function($k) use($m) {
                    return $m->$k;
                }, $key);
            } else {
                return $m->$key;
            }
        }, $this->items);
	}

}
