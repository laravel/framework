<?php namespace Illuminate\Database\Migrations;

abstract class Migration {

	/**
	 * The name of the database connection to use.
	 *
	 * @var string
	 */
	protected $connection;
	
	/**
	 * The schema builder for migrations.
	 * 
	 * @var Illuminate\Database\Schema\Builder
	 */
	 protected $builder;

	/**
	 * Construct an instance of Migration.
	 */
	function __construct()
	{
		$this->builder = app('db')->connection($this->connection)->getSchemaBuilder();
	}

	/**
	 * Get the migration connection name.
	 *
	 * @return string
	 */
	public function getConnection()
	{
		return $this->connection;
	}
	
	/**
	 * Get the schema builder.
	 * 
	 * @return Illuminate\Database\Schema\Builder
	 */
	 public function builder()
	 {
	 	return $this->builder;
	 }

}
