<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;

class HasMany extends HasOneOrMany {

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->query->get();
	}

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return array
	 */
	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, $this->related->newCollection());
		}

		return $models;
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  \Illuminate\Database\Eloquent\Collection  $results
	 * @param  string  $relation
	 * @return array
	 */
	public function match(array $models, Collection $results, $relation)
	{
		return $this->matchMany($models, $results, $relation);
	}

	/**
	 * Add the constraints for a relationship count query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @param  \Illuminate\Database\Eloquent\Builder  $parent
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function getRelationCountQuery(Builder $query, Builder $parent)
	{
		if ($parent->getQuery()->from == $query->getQuery()->from)
		{
			return $this->getRelationCountQueryForSelfRelation($query, $parent);
		}

		return parent::getRelationCountQuery($query, $parent);
	}

	/**
	 * Add the constraints for a relationship count query on the same table.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @param  \Illuminate\Database\Eloquent\Builder  $parent
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function getRelationCountQueryForSelfRelation(Builder $query, Builder $parent)
	{
		$query->select(new Expression('count(*)'));

		$tablePrefix = $this->query->getQuery()->getConnection()->getTablePrefix();

		$query->from($query->getQuery()->from.' as '.$tablePrefix.$hash = $this->getRelationCountHash());

		$key = $this->wrap($this->getQualifiedParentKeyName());

		return $query->where($tablePrefix.$hash.'.'.$this->getPlainForeignKey(), '=', new Expression($key));
	}

	/**
	 * Get a relationship join table hash.
	 *
	 * @return string
	 */
	public function getRelationCountHash()
	{
		return 'self_'.md5(microtime(true));
	}

}
