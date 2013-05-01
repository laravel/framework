<?php namespace Illuminate\Str;

class Str {

	/**
	 * Inflector instance.
	 *
	 * @var Illuminate\Str\Inflector
	 */
	protected static $inflector;

	/**
	 * Get a inflector instance for static call.
	 *
	 * @return Illuminate\Str\Inflector
	 */
	public static function inflector()
	{
		if (is_null(static::$inflector))
		{
			return static::$inflector = new Inflector;
		}

		return static::$inflector;
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
		return call_user_func_array(array(static::inflector(), $method), $parameters);
	}

}
