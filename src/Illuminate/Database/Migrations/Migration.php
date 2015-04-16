<?php namespace Illuminate\Database\Migrations;

use Illuminate\Database\Schema\Builder;

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
	 * @var Builder
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
	 * @return Builder
	 */
	 public function builder()
	 {
	 	return $this->builder;
	 }

}
