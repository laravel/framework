<?php namespace Illuminate\Database\Eloquent\Relations;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

abstract class Relation {

	/**
	 * The Eloquent query builder instance.
	 *
	 * @var \Illuminate\Database\Eloquent\Builder
	 */
	protected $query;

	/**
	 * The parent model instance.
	 *
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	protected $parent;

	/**
	 * The related model instance.
	 *
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	protected $related;

	/**
	 * Create a new relation instance.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder
	 * @param  \Illuminate\Database\Eloquent\Model
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent)
	{
		$this->query = $query;
		$this->parent = $parent;
		$this->related = $query->getModel();

		$this->addConstraints();
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	abstract public function addConstraints();

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	abstract public function addEagerConstraints(array $models);

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return void
	 */
	abstract public function initRelation(array $models, $relation);

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  \Illuminate\Database\Eloquent\Collection  $results
	 * @param  string  $relation
	 * @return array
	 */
	abstract public function match(array $models, Collection $results, $relation);

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	abstract public function getResults();

	/**
	 * Touch all of the related models for the relationship.
	 *
	 * @return void
	 */
	public function touch()
	{
		$table = $this->getRelated()->getTable();

		$column = $this->getRelated()->getUpdatedAtColumn();

		$this->rawUpdate(array($table.'.'.$column => new DateTime));
	}

	/**
	 * Run a raw update against the base query.
	 *
	 * @param  array  $attributes
	 * @return int
	 */
	public function rawUpdate(array $attributes = array())
	{
		return $this->query->update($attributes);
	}

	/**
	 * Remove the original where clause set by the relationship.
	 *
	 * The remaining constraints on the query will be reset and returned.
	 *
	 * @return array
	 */
	public function getAndResetWheres()
	{
		$this->removeFirstWhereClause();

		return $this->getBaseQuery()->getAndResetWheres();
	}

	/**
	 * Remove the first where clause from the relationship query.
	 *
	 * @return void
	 */
	public function removeFirstWhereClause()
	{
		$first = array_shift($this->getBaseQuery()->wheres);

		$bindings = $this->getBaseQuery()->getBindings();

		// When resetting the relation where clause, we want to shift the first element
		// off of the bindings, leaving only the constraints that the developers put
		// as "extra" on the relationships, and not original relation constraints.
		if (array_key_exists('value', $first))
		{
			$bindings = array_slice($bindings, 1);
		}

		$this->getBaseQuery()->setBindings(array_values($bindings));
	}

	/**
	 * Get all of the primary keys for an array of models.
	 *
	 * @param  array  $models
	 * @return array
	 */
	protected function getKeys(array $models)
	{
		return array_values(array_map(function($value)
		{
			return $value->getKey();

		}, $models));
	}

	/**
	 * Get the underlying query for the relation.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Get the base query builder driving the Eloquent builder.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function getBaseQuery()
	{
		return $this->query->getQuery();
	}

	/**
	 * Get the parent model of the relation.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Get the related model of the relation.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getRelated()
	{
		return $this->related;
	}

	/**
	 * Get the name of the "created at" column.
	 *
	 * @return string
	 */
	public function createdAt()
	{
		return $this->parent->getCreatedAtColumn();
	}

	/**
	 * Get the name of the "updated at" column.
	 *
	 * @return string
	 */
	public function updatedAt()
	{
		return $this->parent->getUpdatedAtColumn();
	}

	/**
	 * Handle dynamic method calls to the relationship.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$result = call_user_func_array(array($this->query, $method), $parameters);

		if ($result === $this->query) return $this;

		return $result;
	}

}