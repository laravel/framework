<?php namespace Illuminate\Str;

class Str {

	/**
	 * The active inflector instances.
	 *
	 * @var Illuminate\Str\Inflector
	 */
	protected $inflector;

	/**
	 * The active static inflector instances.
	 *
	 * @var Illuminate\Str\Inflector
	 */
	protected static $staticInflector;

	/**
	 * Get a inflector instance.
	 *
	 * @return Illuminate\Str\Inflector
	 */
	public function inflector()
	{
		if (is_null($this->inflector))
		{
			return $this->inflector = new Inflector;
		}

		return $this->inflector;
	}

	/**
	 * Get a inflector instance for static call.
	 *
	 * @return Illuminate\Str\Inflector
	 */
	public static function staticInflector()
	{
		if (is_null(static::$staticInflector))
		{
			return static::$staticInflector = new Inflector;
		}

		return static::$staticInflector;
	}

	/**
	 * Dynamically pass methods to the string inflector.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->inflector(), $method), $parameters);
	}

	/**
	 * Dynamically pass static methods to the static string inflector.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::staticInflector(), $method), $parameters);
	}

}
