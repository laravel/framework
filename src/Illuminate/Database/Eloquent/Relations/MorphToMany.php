<?php namespace Illuminate\Database\Eloquent\Relations;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;

class MorphToMany extends BelongsToMany {

	/**
	 * The type of the polymorphic relation.
	 *
	 * @var string
	 */
	protected $morphType;

	/**
	 * Create a new has many relationship instance.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @param  \Illuminate\Database\Eloquent\Model  $parent
	 * @param  string  $name
	 * @param  string  $table
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @param  string  $relationName
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent, $name, $table, $foreignKey, $otherKey, $relationName = null)
	{
		$this->morphType = $name.'_type';

		parent::__construct($query, $parent, $table, $foreignKey, $otherKey, $relationName);
	}

	/**
	 * Set the where clause for the relation query.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	protected function setWhere()
	{
		parent::setWhere();

		$this->query->where($this->morphType, get_class($this->parent));

		return $this;
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		parent::addEagerConstraints($models);

		$this->query->where($this->morphType, get_class($this->parent));
	}

	/**
	 * Create a new pivot attachment record.
	 *
	 * @param  int   $id
	 * @param  bool  $timed
	 * @return array
	 */
	protected function createAttachRecord($id, $timed)
	{
		$record = parent::createAttachRecord($id, $timed);

		return array_add($record, $this->morphType, get_class($this->parent));
	}

	/**
	 * Create a new query builder for the pivot table.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function newPivotQuery()
	{
		$query = $this->newPivotStatement();

		return $query->where($this->foreignKey, $this->parent->getKey());
	}

	/**
	 * Get a new plain query builder for the pivot table.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function newPivotStatement()
	{
		return parent::newPivotStatement()->where($this->morphType, get_class($this->parent));
	}

	/**
	 * Create a new pivot model instance.
	 *
	 * @param  array  $attributes
	 * @param  bool   $exists
	 * @return \Illuminate\Database\Eloquent\Relation\Pivot
	 */
	public function newPivot(array $attributes = array(), $exists = false)
	{
		$pivot = new MorphPivot($this->parent, $attributes, $this->table, $exists);

		$pivot->setPivotKeys($this->foreignKey, $this->otherKey);

		$pivot->setMorphType($this->morphType);

		return $pivot;
	}

}