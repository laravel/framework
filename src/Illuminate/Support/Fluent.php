<?php namespace Illuminate\Support;

use Closure;

class Fluent {

	/**
	 * All of the attributes set on the container.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Create a new fluent container instance.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct($attributes = array())
	{
		foreach ($attributes as $key => $value)
		{
			$this->$key = $value;
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
		if (array_key_exists($key, $this->attributes))
		{
			return $this->attributes[$key];
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
		return $this->attributes;
	}

	/**
	 * Handle dynamic calls to the container to set attributes.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return Illuminate\Support\Fluent
	 */
	public function __call($method, $parameters)
	{
		$this->$method = count($parameters) > 0 ? $parameters[0] : true;

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
		$this->attributes[$key] = $value;
	}

	/**
	 * Dynamically check if an attribute is set.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Dynamically unset an attribute.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

}