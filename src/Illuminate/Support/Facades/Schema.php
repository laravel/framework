<?php namespace Illuminate\Support\Facades;

use Illuminate\Foundation\Application;

class Schema extends Facade {

	/**
	 * Get a schema builder instance for a connection.
	 *
	 * @param  string  $name
	 * @return Illuminate\Database\Schema\Builder
	 */
	public static function connection($name)
	{
		return Application::getCurrent()['db']->connection($name)->getSchemaBuilder();
	}

	/**
	 * Get a SchemaBuilder from the Connection of the registered component 'db'
	 *
	 * @return Illuminate\Database\Schema\Builder
	 */
	public static function getCurrent() {
		return Application::getCurrent()['db']->connection()->getSchemaBuilder();;
	}

}