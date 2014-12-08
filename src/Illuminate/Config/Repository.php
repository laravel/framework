<?php namespace Illuminate\Config;

use ArrayAccess;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class Repository implements ArrayAccess, ConfigContract {

	/**
	 * All of the configuration items.
	 *
	 * @var array
	 */
	protected $items = [];

	/**
	 * Create a new configuration repository.
	 *
	 * @param  array  $items
	 * @return void
	 */
	public function __construct(array $items = array())
	{
		$this->items = $items;
	}

	/**
	 * Determine if the given configuration value exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		$default = microtime(true);

		return $this->get($key, $default) !== $default;
	}

	/**
	 * Get the specified configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return array_get($this->items, $key, $default);
	}

	/**
	 * Set a given configuration value.
	 *
	 * @param  array|string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function set($key, $value = null)
	{
		if (is_array($key))
		{
			foreach ($key as $innerKey => $innerValue)
			{
				array_set($this->items, $innerKey, $innerValue);
			}
		}
		else
		{
			array_set($this->items, $key, $value);
		}
	}

	/**
	 * Prepend a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function prepend($key, $value)
	{
		$array = $this->get($key);

		array_unshift($array, $value);

		$this->set($key, $array);
	}

	/**
	 * Push a value onto an array configuration value.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function push($key, $value)
	{
		$array = $this->get($key);

		$array[] = $value;

		$this->set($key, $array);
	}

	/**
	 * Determine if the given configuration option exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Get a configuration option.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->get($key);
	}

	/**
	 * Set a configuration option.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Unset a configuration option.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->set($key, null);
	}

}
