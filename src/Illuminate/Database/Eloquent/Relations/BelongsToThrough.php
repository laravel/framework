<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;
use Illuminate\Database\Eloquent\Relations\Relation;

class BelongsToThrough extends Relation
{
    use SupportsDefaultModels;

    /**
     * The "through" child model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $throughChild;

    /**
     * The far child model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $farChild;

    /**
     * The near key on the relationship.
     *
     * @var string
     */
    protected $firstKey;

    /**
     * The far key on the relationship.
     *
     * @var string
     */
    protected $secondKey;

    /**
     * The owner key on the relationship.
     *
     * @var string
     */
    protected $ownerKey;

    /**
     * The local key on the intermediary model.
     *
     * @var string
     */
    protected $secondOwnerKey;

    /**
     * The name of the relationship.
     *
     * @var string
     */
    protected $relation;

    /**
     * The count of self joins.
     *
     * @var int
     */
    protected static $selfJoinCount = 0;

    /**
     * Create a new belongs to through relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $farChild
     * @param  \Illuminate\Database\Eloquent\Model  $throughChild
     * @param  string  $firstKey
     * @param  string  $secondKey
     * @param  string  $ownerKey
     * @param  string  $secondOwnerKey
     * @param  string  $relation
     * @return void
     */
    public function __construct(Builder $query, Model $farChild, Model $throughChild, $firstKey, $secondKey, $ownerKey, $secondOwnerKey, $relation)
    {
        $this->ownerKey = $ownerKey;
        $this->secondOwnerKey = $secondOwnerKey;
        $this->relation = $relation;
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;

        $this->throughChild = $throughChild;
        $this->farChild = $farChild;

        parent::__construct($query, $throughChild);
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->first() ?: $this->getDefaultFor($this->parent);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $throughValue = $this->farChild[$this->secondKey];

        $this->performJoin();

        if (static::$constraints) {
            $this->query->where($this->getQualifiedSecondOwnerKeyName(), '=', $throughValue);
        }

    }

    /**
     * Set the join clause on the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return void
     */
    protected function performJoin(Builder $query = null)
    {
        $query = $query ?: $this->query;

        $ownerKey = $this->getQualifiedOwnerKeyName();

        $query->join($this->throughChild->getTable(), $this->getQualifiedFirstKeyName(), '=', $ownerKey);

        if ($this->throughChildSoftDeletes()) {
            $query->whereNull($this->throughChild->getQualifiedDeletedAtColumn());
        }
    }

    /**
     * Determine whether "through" child of the relation uses Soft Deletes.
     *
     * @return bool
     */
    public function throughChildSoftDeletes()
    {
        return in_array(SoftDeletes::class, class_uses_recursive(
            get_class($this->throughChild)
        ));
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this->query->whereIn(
            $this->getQualifiedSecondOwnerKeyName(), $this->getKeys($models, $this->secondKey)
        );
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
        foreach ($models as $model) {
            $model->setRelation($relation, $this->getDefaultFor($model));
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their childs.
     *
     * @param  array   $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the childs models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getAttribute($this->firstKey)])) {
                $value = $dictionary[$key];
                $model->setRelation(
                    $relation, reset($value)
                );
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        $dictionary = [];

        // First we will create a dictionary of models keyed by the foreign key of the
        // relationship as this will allow us to quickly access all of the related
        // models without having to do nested looping which will be quite slow.
        foreach ($results as $result) {
            $dictionary[$result->{$this->ownerKey}][] = $result;
        }

        return $dictionary;
    }

    /**
     * Update the parent model on the relationship.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function update(array $attributes)
    {
        return $this->getResults()->fill($attributes)->save();
    }

    /**
     * Associate the model instance to the given parent.
     *
     * @param  \Illuminate\Database\Eloquent\Model|int|string  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        $secondOwnerKey = $model instanceof Model ? $model->getAttribute($this->secondOwnerKey) : $model;

        $this->farChild->setAttribute($this->secondKey, $secondOwnerKey);

        if ($model instanceof Model) {
            $this->farChild->setRelation($this->relation, $model);
        }

        return $this->farChild;
    }

    /**
     * Dissociate previously associated model from the given parent.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function dissociate()
    {
        $this->farChild->setAttribute($this->firstKey, null);

        return $this->farChild->setRelation($this->relation, null);
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($parentQuery->getQuery()->from == $query->getQuery()->from) {
            return $this->getRelationExistenceQueryForSelfRelation($query, $parentQuery, $columns);
        }

        $this->performJoin($query);

        return $query->select($columns)->whereColumn(
            $this->getQualifiedForeignKeyName(), '=', $this->getQualifiedSecondOwnerKeyName()
        );
    }

    /**
     * Add the constraints for a relationship query on the same table.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $query->join($this->throughChild->getTable(), $this->getQualifiedParentKeyName(), '=', $hash.'.'.$this->ownerKey);

        if ($this->throughParentSoftDeletes()) {
            $query->whereNull($this->throughParent->getQualifiedDeletedAtColumn());
        }

        $query->getModel()->setTable($hash);

        return $query->whereColumn(
            $hash.'.'.$query->getModel()->qualifyColumn($this->secondKey), '=', $this->getQualifiedSecondOwnerKeyName()
        );
    }

    /**
     * Make a new related instance for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newRelatedInstanceFor(Model $parent)
    {
        return $this->related->newInstance();
    }

    /**
     * Get a relationship join table hash.
     *
     * @return string
     */
    public function getRelationCountHash()
    {
        return 'laravel_reserved_'.static::$selfJoinCount++;
    }

    /**
     * Determine if the related model has an auto-incrementing ID.
     *
     * @return bool
     */
    protected function relationHasIncrementingId()
    {
        return $this->related->getIncrementing() &&
                                $this->related->getKeyType() === 'int';
    }

    /**
     * Get the qualified foreign key on the related model.
     *
     * @return string
     */
    public function getQualifiedFarKeyName()
    {
        return $this->getQualifiedForeignKeyName();
    }

    /**
     * Get the qualified foreign key on the "through" model.
     *
     * @return string
     */
    public function getQualifiedFirstKeyName()
    {
        return $this->throughChild->qualifyColumn($this->firstKey);
    }

    /**
     * Get the qualified foreign key on the far child model.
     *
     * @return string
     */
    public function getQualifiedForeignKeyName()
    {
        return $this->farChild->qualifyColumn($this->secondKey);
    }

    /**
     * Get the qualified owner key on the related model.
     *
     * @return string
     */
    public function getQualifiedOwnerKeyName()
    {
        return $this->related->qualifyColumn($this->ownerKey);
    }

    /**
     * Get the qualified second owner key on the related model.
     *
     * @return string
     */
    public function getQualifiedSecondOwnerKeyName()
    {
        return $this->parent->qualifyColumn($this->secondOwnerKey);
    }
}
