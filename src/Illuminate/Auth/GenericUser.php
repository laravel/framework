<?php namespace Illuminate\Auth;

class GenericUser implements UserInterface {

	/**
	 * All of the user's attributes.
	 *
	 * @var array
	 */
	protected $attributes;

	/**
	 * Create a new generic User object.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes)
	{
		$this->attributes = $attributes;
	}

	/**
	 * Get the unique identifier for the user.
	 *
	 * @return mixed
	 */
	public function getAuthIdentifier()
	{
		return $this->attributes['id'];
	}

	/**
	 * Get the password for the user.
	 *
	 * @return string
	 */
	public function getAuthPassword()
	{
		return $this->attributes['password'];
	}

	/**
	 * Dynamically access the user's attributes.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->attributes[$key];
	}

	/**
	 * Dynamically set an attribute on the user.
	 *
	 * @param  string  $key
	 * @param  mied    $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->attributes[$key] = $value;
	}

	/**
	 * Dynamically check if a value is set on the user.
	 *
	 * @return bool
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Dynamically unset a value on the user.
	 *
	 * @return bool
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

}