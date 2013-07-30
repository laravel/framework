<?php namespace Illuminate\Database\Eloquent\Relations;

use DateTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
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
		$column = $this->getRelated()->getUpdatedAtColumn();

		$this->rawUpdate(array($column => $this->getRelated()->freshTimestamp()));
	}

	/**
	 * Restore all of the soft deleted related models.
	 *
	 * @return int
	 */
	public function restore()
	{
		return $this->query->withTrashed()->restore();
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
		// When a model is "soft deleting", the "deleted at" where clause will be the
		// first where clause on the relationship query, so we will actually clear
		// the second where clause as that is the lazy loading relations clause.
		if ($this->query->getModel()->isSoftDeleting())
		{
			$this->removeSecondWhereClause();
		}

		// When the model isn't soft deleting the where clause added by the lazy load
		// relation query will be the first where clause on this query, so we will
		// remove that to make room for the eager load constraints on the query.
		else
		{
			$this->removeFirstWhereClause();
		}

		return $this->getBaseQuery()->getAndResetWheres();
	}

	/**
	 * Remove the first where clause from the relationship query.
	 *
	 * @return void
	 */
	protected function removeFirstWhereClause()
	{
		$first = array_shift($this->getBaseQuery()->wheres);

		return $this->removeWhereBinding($first);
	}

	/**
	 * Remove the second where clause from the relationship query.
	 *
	 * @return void
	 */
	protected function removeSecondWhereClause()
	{
		$wheres =& $this->getBaseQuery()->wheres;

		// We'll grab the second where clause off of the set of wheres, and then reset
		// the where clause keys so there are no gaps in the numeric keys. Then we
		// remove the binding from the query so it doesn't mess things when run.
		$second = $wheres[1]; unset($wheres[1]);

		$wheres = array_values($wheres);

		return $this->removeWhereBinding($second);
	}

	/**
	 * Remove a where clause from the relationship query.
	 *
	 * @param  array  $clause
	 * @return void
	 */
	public function removeWhereBinding($clause)
	{
		$query = $this->getBaseQuery();

		$bindings = $query->getBindings();

		// When resetting the relation where clause, we want to shift the first element
		// off of the bindings, leaving only the constraints that the developers put
		// as "extra" on the relationships, and not original relation constraints.
		if (array_key_exists('value', $clause))
		{
			$bindings = array_slice($bindings, 1);
		}

		$query->setBindings(array_values($bindings));
	}

	/**
	 * Add the constraints for a relationship count query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function getRelationCountQuery(Builder $query)
	{
		$query->select(new Expression('count(*)'));

		$key = $this->wrap($this->parent->getQualifiedKeyName());

		return $query->where($this->getForeignKey(), '=', new Expression($key));
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
	 * Get the name of the related model's "updated at" column.
	 *
	 * @return string
	 */
	public function relatedUpdatedAt()
	{
		return $this->related->getUpdatedAtColumn();
	}

	/**
	 * Wrap the given value with the parent query's grammar.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function wrap($value)
	{
		return $this->parent->getQuery()->getGrammar()->wrap($value);
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