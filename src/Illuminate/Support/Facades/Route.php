<?php namespace Illuminate\Support\Facades;

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
		return static::$app['router']->addFilter($name, $callback);
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
		return static::$app['router']->matchFilter($pattern, $name);
	}

	/**
	 * Determine if the current route matches a given name.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public static function is($name)
	{
		return static::$app['router']->currentRouteNamed($name);
	}

	/**
	 * Determine if the current route uses a given controller action.
	 *
	 * @param  string  $action
	 * @return bool
	 */
	public static function uses($action)
	{
		return static::$app['router']->currentRouteUses($action);
	}

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'router'; }

}