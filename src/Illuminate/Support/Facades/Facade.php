<?php namespace Illuminate\Support\Facades;

use Mockery\MockInterface;

abstract class Facade {

	/**
	 * The application instance being facaded.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected static $app;

	/**
	 * The resolved object instances.
	 *
	 * @var array
	 */
	protected static $resolvedInstance;

	/**
	 * Hotswap the underlying instance behind the facade.
	 *
	 * @param  mixed  $instance
	 * @return void
	 */
	public static function swap($instance)
	{
		static::$resolvedInstance[static::getFacadeAccessor()] = $instance;

		static::$app->instance(static::getFacadeAccessor(), $instance);
	}

	/**
	 * Initiate a mock expectation on the facade.
	 *
	 * @param  dynamic
	 * @return \Mockery\Expectation
	 */
	public static function shouldReceive()
	{
		$name = static::getFacadeAccessor();

		if (static::isMock())
		{
			$mock = static::$resolvedInstance[$name];
		}
		else
		{
			static::$resolvedInstance[$name] = $mock = \Mockery::mock(static::getMockableClass($name));

			static::$app->instance($name, $mock);
		}

		return call_user_func_array(array($mock, 'shouldReceive'), func_get_args());
	}

	/**
	 * Determines whether a mock is set as the instance of the facade.
	 *
	 * @return bool
	 */
	protected static function isMock()
	{
		$name = static::getFacadeAccessor();

		return isset(static::$resolvedInstance[$name]) and static::$resolvedInstance[$name] instanceof MockInterface;
	}

	/**
	 * Get the mockable class for the bound instance.
	 *
	 * @return string
	 */
	protected static function getMockableClass()
	{
		return get_class(static::getFacadeRoot());
	}

	/**
	 * Get the root object behind the facade.
	 *
	 * @return mixed
	 */
	public static function getFacadeRoot()
	{
		return static::resolveFacadeInstance(static::getFacadeAccessor());
	}

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		throw new \RuntimeException("Facade does not implement getFacadeAccessor method.");
	}

	/**
	 * Resolve the facade root instance from the container.
	 *
	 * @param  string  $name
	 * @return mixed
	 */
	protected static function resolveFacadeInstance($name)
	{
		if (is_object($name)) return $name;

		if (isset(static::$resolvedInstance[$name]))
		{
			return static::$resolvedInstance[$name];
		}

		return static::$resolvedInstance[$name] = static::$app[$name];
	}

	/**
	 * Clear a resolved facade instance.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public static function clearResolvedInstance($name)
	{
		unset(static::$resolvedInstance[$name]);
	}

	/**
	 * Clear all of the resolved instances.
	 *
	 * @return void
	 */
	public static function clearResolvedInstances()
	{
		static::$resolvedInstance = array();
	}

	/**
	 * Get the application instance behind the facade.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public static function getFacadeApplication()
	{
		return static::$app;
	}

	/**
	 * Set the application instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public static function setFacadeApplication($app)
	{
		static::$app = $app;
	}

	/**
	 * Handle dynamic, static calls to the object.
	 *
	 * @param  string  $method
	 * @param  array   $args
	 * @return mixed
	 */
	public static function __callStatic($method, $args)
	{
		$instance = static::resolveFacadeInstance(static::getFacadeAccessor());

		switch (count($args))
		{
			case 0:
				return $instance->$method();

			case 1:
				return $instance->$method($args[0]);

			case 2:
				return $instance->$method($args[0], $args[1]);

			case 3:
				return $instance->$method($args[0], $args[1], $args[2]);

			case 4:
				return $instance->$method($args[0], $args[1], $args[2], $args[3]);

			default:
				return call_user_func_array(array($instance, $method), $args);
		}
	}

}