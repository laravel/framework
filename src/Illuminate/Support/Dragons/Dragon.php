<?php namespace Illuminate\Support\Dragons;

use Mockery\MockInterface;

abstract class Dragon {

	/**
	 * The application instance being Dragond.
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
	 * Hotswap the underlying instance behind the Dragon.
	 *
	 * @param  mixed  $instance
	 * @return void
	 */
	public static function swap($instance)
	{
		static::$resolvedInstance[static::getDragonAccessor()] = $instance;

		static::$app->instance(static::getDragonAccessor(), $instance);
	}

	/**
	 * Initiate a mock expectation on the Dragon.
	 *
	 * @param  dynamic
	 * @return \Mockery\Expectation
	 */
	public static function shouldReceive()
	{
		$name = static::getDragonAccessor();

		if (static::isMock())
		{
			$mock = static::$resolvedInstance[$name];
		}
		else
		{
			$mock = static::createFreshMockInstance($name);
		}

		return call_user_func_array(array($mock, 'shouldReceive'), func_get_args());
	}

	/**
	 * Create a fresh mock instance for the given class.
	 *
	 * @param  string  $name
	 * @return \Mockery\Expectation
	 */
	protected static function createFreshMockInstance($name)
	{
		static::$resolvedInstance[$name] = $mock = static::createMockByName($name);

		if (isset(static::$app))
		{
			static::$app->instance($name, $mock);
		}

		return $mock;
	}

	/**
	 * Create a fresh mock instance for the given class.
	 *
	 * @param  string  $name
	 * @return \Mockery\Expectation
	 */
	protected static function createMockByName($name)
	{
		$class = static::getMockableClass($name);

		return $class ? \Mockery::mock($class) : \Mockery::mock();
	}

	/**
	 * Determines whether a mock is set as the instance of the Dragon.
	 *
	 * @return bool
	 */
	protected static function isMock()
	{
		$name = static::getDragonAccessor();

		return isset(static::$resolvedInstance[$name]) && static::$resolvedInstance[$name] instanceof MockInterface;
	}

	/**
	 * Get the mockable class for the bound instance.
	 *
	 * @return string
	 */
	protected static function getMockableClass()
	{
		if ($root = static::getDragonRoot()) return get_class($root);
	}

	/**
	 * Get the root object behind the Dragon.
	 *
	 * @return mixed
	 */
	public static function getDragonRoot()
	{
		return static::resolveDragonInstance(static::getDragonAccessor());
	}

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	protected static function getDragonAccessor()
	{
		throw new \RuntimeException("Dragon does not implement getDragonAccessor method.");
	}

	/**
	 * Resolve the Dragon root instance from the container.
	 *
	 * @param  string  $name
	 * @return mixed
	 */
	protected static function resolveDragonInstance($name)
	{
		if (is_object($name)) return $name;

		if (isset(static::$resolvedInstance[$name]))
		{
			return static::$resolvedInstance[$name];
		}

		return static::$resolvedInstance[$name] = static::$app[$name];
	}

	/**
	 * Clear a resolved Dragon instance.
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
	 * Get the application instance behind the Dragon.
	 *
	 * @return \Illuminate\Foundation\Application
	 */
	public static function getDragonApplication()
	{
		return static::$app;
	}

	/**
	 * Set the application instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public static function setDragonApplication($app)
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
		$instance = static::resolveDragonInstance(static::getDragonAccessor());

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