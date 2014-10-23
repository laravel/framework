<?php namespace Illuminate\Routing\Annotations\Annotations;

use ArrayAccess;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Annotations\MethodEndpoint;
use Illuminate\Routing\Annotations\EndpointCollection;

abstract class Annotation implements ArrayAccess {

	/**
	 * The value array.
	 *
	 * @var array
	 */
	protected $values;

	/**
	 * Create a new annotation instance.
	 *
	 * @param  array  $values
	 * @return void
	 */
	public function __construct(array $values)
	{
		$this->values = $values;
	}

	/**
	 * Apply the annotation's settings to the given endpoint.
	 *
	 * @param  MethodEndpoint  $endpoint
	 * @param  ReflectionMethod  $method
	 * @return void
	 */
	public function modify(MethodEndpoint $endpoint, ReflectionMethod $method)
	{
		//
	}

	/**
	 * Apply the annotation's settings to the given endpoint collection.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @param  ReflectionClass  $class
	 * @return void
	 */
	public function modifyCollection(EndpointCollection $endpoints, ReflectionClass $class)
	{
		//
	}

	/**
	 * Determine if the value at a given offset exists.
	 *
	 * @param  string  $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->values);
	}

	/**
	 * Get the value at a given offset.
	 *
	 * @param  string  $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->values[$offset];
	}

	/**
	 * Set the value at a given offset.
	 *
	 * @param  string  $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->values[$offset] = $value;
	}

	/**
	 * Remove the value at a given offset.
	 *
	 * @param  string  $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);
	}

	/**
	 * Dynamically get a property on the annotation.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if ($this->offsetExists($key))
		{
			return $this->values[$key];
		}
	}

	/**
	 * Dynamically set a property on the annotation.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->values[$key] = $value;
	}

}
