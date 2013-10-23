<?php namespace Illuminate\Support\Facades;

class Route extends Facade {

	/**
	 * Determine if the current route matches a given name.
	 *
	 * @param  string  $name
	 * @return bool
	 */
	public static function is($name)
	{
		return static::$app['router']->current()->getName() == $name;
	}

	/**
	 * Determine if the current route uses a given controller action.
	 *
	 * @param  string  $action
	 * @return bool
	 */
	public static function uses($action)
	{
		$currentAction = $app['router']->current()->getAction();
		return isset($currentAction['controller']) && $currentAction['controller'] == $action;
	}

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'router'; }

}