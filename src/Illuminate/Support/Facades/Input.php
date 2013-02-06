<?php namespace Illuminate\Support\Facades;

class Input extends Facade {

	/**
	 * Get an item from the input data.
	 *
	 * This method is used for all request verbs (GET, POST, PUT, and DELETE)
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public static function get($key = null, $default = null)
	{
		return static::Current()->input($key, $default);
	}

	/**
	 * Get the registered component 'request'.
	 *
	 * @return Illuminate\Http\Request
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['request'];
	}

}