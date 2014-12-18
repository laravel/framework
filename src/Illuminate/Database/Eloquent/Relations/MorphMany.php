<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class MorphMany extends MorphOneOrMany {

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->initInverseRelation($this->query->get());
	}

	/**
	 * Initialize the parent relationship on a set of models.
	 *
	 * @param  \Illuminate\Database\Eloquent\Collection  $models
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	protected function initInverseRelation(Collection $models)
	{
		if ( ! empty($this->relationToParent) && !$models->isEmpty())
		{
			$relation = $this->relationToParent;
			$parent   = $this->parent;

			foreach($models as $model)
			{
				$model->setRelation($relation, $parent);
			}
		}

		return $models;
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

}
