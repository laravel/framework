<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;

class Pivot extends Model {

	/**
	 * The name of the foreign key column.
	 *
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * The name of the "other key" column.
	 *
	 * @var string
	 */
	protected $otherKey;

	/**
	 * Create a new pivot model instance.
	 *
	 * @param  array   $attributes
	 * @param  string  $table
	 * @param  string  $connection
	 * @param  bool    $exists
	 * @return void
	 */
	public function __construct($attributes, $table, $connection, $exists = false)
	{
		// The pivot model is a "dynamic" model since we will set the tables dynamically
		// for the instance. This allows it work for any intermediate tables for the
		// many to many relationship that are defined by this developer's classes.
		parent::__construct($attributes);

		$this->setTable($table);

		$this->setConnection($connection);

		$this->exists = $exists;

		$this->timestamps = array_key_exists('created_at', $attributes);
	}

	/**
	 * Set the keys for a save update query.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder
	 * @return void
	 */
	protected function setKeysForSaveQuery($query)
	{
		$query->where($this->foreignKey, $this->getAttribute($this->foreignKey));

		$query->where($this->otherKey, $this->getAttribute($this->otherKey));
	}

	/**
	 * Delete the pivot model record from the database.
	 *
	 * @return int
	 */
	public function delete()
	{
		$foreign = $this->getAttribute($this->foreignKey);

		$query = $this->newQuery()->where($this->foreignKey, $foreign);

		return $query->where($this->otherKey, $this->getAttribute($this->otherKey))->delete();
	}

	/**
	 * Get the foreign key column name.
	 *
	 * @return string
	 */
	public function getForeignKey()
	{
		return $this->foreignKey;
	}

	/**
	 * Get the "other key" column name.
	 *
	 * @return string
	 */
	public function getOtherKey()
	{
		return $this->otherKey;
	}

	/**
	 * Set the key names for the pivot model instance.
	 *
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @return void
	 */
	public function setPivotKeys($foreignKey, $otherKey)
	{
		$this->foreignKey = $foreignKey;

		$this->otherKey = $otherKey;
	}

}