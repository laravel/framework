<?php namespace Illuminate\Support\Facades;

abstract class Facade {
	
	/**
	 * Facade function for a Singleton, or registered component
	 */
	public static function getCurrent() {
		throw new \RuntimeException("Facade does not implement static getCurrent method.");
	}
	
	/**
	 * Handle dynamic, static calls to the Current() object.
	 *
	 * @param  string  $method
	 * @param  array   $args
	 * @return mixed
	 */
	public static function __callStatic($method, $args)
	{
		return call_user_func_array(array(static::getCurrent(), $method), $args);
	}

}