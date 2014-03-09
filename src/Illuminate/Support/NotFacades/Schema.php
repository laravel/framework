<?php namespace Illuminate\Support\NotFacades;

/**
 * @see \Illuminate\Database\Schema\Builder
 */
class Schema extends NotAFacade {

	/**
	 * Get a schema builder instance for a connection.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Database\Schema\Builder
	 */
	public static function connection($name)
	{
		return static::$app['db']->connection($name)->getSchemaBuilder();
	}

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getNotAFacadeAccessor()
	{
		return static::$app['db']->connection()->getSchemaBuilder();
	}

}