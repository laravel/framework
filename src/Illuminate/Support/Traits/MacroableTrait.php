<?php namespace Illuminate\Support\Traits;

trait MacroableTrait {

	/**
	 * The registered string macros.
	 *
	 * @var array
	 */
	protected static $macros = array();

	/**
	 * Register a custom macro.
	 *
	 * @param  string    $name
	 * @param  callable  $macro
	 * @return void
	 */
	public static function macro($name, $macro)
	{
		static::$macros[$name] = $macro;
	}

	/**
	 * Dynamically handle calls to the class.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public static function __callStatic($method, $parameters)
	{
		if (isset(static::$macros[$method]))
		{
			return call_user_func_array(static::$macros[$method], $parameters);
		}

		throw new \BadMethodCallException("Method {$method} does not exist.");
	}

}
