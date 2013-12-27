<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MorphTo extends BelongsTo {

	/**
	 * The type key of the parent model.
	 *
	 * @var string
	 */
	protected $typeKey;

	/**
	 * Create a new belongs to relationship instance.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @param  \Illuminate\Database\Eloquent\Model  $parent
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent, $foreignKey, $otherKey, $typeKey)
	{
		$this->typeKey = $typeKey;

		parent::__construct($query, $parent, $foreignKey, $otherKey, 'morphTo');
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
		$foreign = $this->foreignKey;

		$other = $this->otherKey;

		$typeKey = $this->typeKey;

		// First we will get to build a dictionary of the child models by their primary
		// key of the relationship, then we can easily match the children back onto
		// the parents using that dictionary and the primary key of the children.
		$dictionary = array();
		foreach ($results as $result)
		{
			$dictionary[$result->getAttribute($other).get_class($result)] = $result;
		}

		// Once we have the dictionary constructed, we can loop through all the parents
		// and match back onto their children using these keys of the dictionary and
		// the primary key of the children to map them onto the correct instances.
		foreach ($models as $model)
		{
			if (isset($dictionary[$model->$foreign.$model->$typeKey]))
			{
				$model->setRelation($relation, $dictionary[$model->$foreign.$model->$typeKey]);
			}
		}

		return $models;
	}

}
