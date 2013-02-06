<?php namespace Illuminate\Support\Facades;

use Illuminate\Foundation\Application;

class Route extends Facade {

	/**
	 * Register a new filter with the application.
	 *
	 * @param  string   $name
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public static function filter($name, $callback)
	{
		return static::addFilter($name, $callback);
	}

	/**
	 * Tie a registered middleware to a URI pattern.
	 *
	 * @param  string  $pattern
	 * @param  string|array  $name
	 * @return void
	 */
	public static function when($pattern, $name)
	{
		return static::matchFilter($pattern, $name);
	}

	/**
	 * Determine if the current route matches a given name.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public static function is($name)
	{
		return static::currentRouteNamed($name);
	}

	/**
	 * Determine if the current route uses a given controller action.
	 *
	 * @param  string  $action
	 * @return bool
	 */
	public static function uses($action)
	{
		return static::currentRouteUses($action);
	}

	/**
	 * Get the registered component 'router'.
	 *
	 * @return Illuminate\Routing\Router
	 */
	public static function Current() {
		return Illuminate\Foundation\Application::Current()['router'];
	}

}