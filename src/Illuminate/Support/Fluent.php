<?php namespace Illuminate\Support;

use ArrayObject;
use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Fluent extends ArrayObject implements Arrayable, Jsonable, JsonSerializable {

	/**
	 * Create a new fluent container instance.
	 *
	 * @param  array|object	$attributes
	 * @return void
	 */
	public function __construct($attributes = array())
	{
		foreach ($attributes as $key => $value)
		{
			$this->offsetSet($key, $value);
		}
	}

	/**
	 * Get an attribute from the container.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if ($this->offsetExists($key))
		{
			return $this->offsetGet($key);
		}

		return value($default);
	}

	/**
	 * Get the attributes from the container.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->getArrayCopy();
	}

	/**
	 * Convert the Fluent instance to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->getAttributes();
	}

	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Convert the Fluent instance to JSON.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Handle dynamic calls to the container to set attributes.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return $this
	 */
	public function __call($method, $parameters)
	{
		$this->offsetSet($method, count($parameters) > 0 ? $parameters[0] : true);

		return $this;
	}

	/**
	 * Dynamically retrieve the value of an attribute.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Dynamically set the value of an attribute.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->offsetSet($key, $value);
	}

	/**
	 * Dynamically check if an attribute is set.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __isset($key)
	{
		return $this->offsetExists($key);
	}

	/**
	 * Dynamically unset an attribute.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		$this->offsetUnset($key);
	}

}
