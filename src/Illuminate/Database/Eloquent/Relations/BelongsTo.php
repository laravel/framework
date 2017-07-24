<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Concerns\SupportsDefaultModels;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class BelongsTo extends Relation
{
    use SupportsDefaultModels;

    /**
     * The child model instance of the relation.
     */
    protected $child;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The associated key on the parent model.
     *
     * @var string
     */
    protected $ownerKey;

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
     * Create a new belongs to relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $child
     * @param  string  $foreignKey
     * @param  string  $ownerKey
     * @param  string  $relation
     * @return void
     */
    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        $this->ownerKey = $ownerKey;
        $this->relation = $relation;
        $this->foreignKey = $foreignKey;

        // In the underlying base relationship class, this variable is referred to as
        // the "parent" since most relationships are not inversed. But, since this
        // one is we will create a "child" variable for much better readability.
        $this->child = $child;

        parent::__construct($query, $child);
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
        if (static::$constraints) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            $table = $this->related->getTable();

            $this->query->where($table.'.'.$this->ownerKey, '=', $this->child->{$this->foreignKey});
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        // We'll grab the primary key name of the related models since it could be set to
        // a non-standard name and not "id". We will then construct the constraint for
        // our eagerly loading query so it returns the proper models from execution.
        $key = $this->related->getTable().'.'.$this->ownerKey;

        $this->query->whereIn($key, $this->getEagerModelKeys($models));
    }

    /**
     * Gather the keys from an array of related models.
     *
     * @param  array  $models
     * @return array
     */
    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        // First we need to gather all of the keys from the parent models so we know what
        // to query for via the eager loading query. We will add them to an array then
        // execute a "where in" statement to gather up all of those related records.
        foreach ($models as $model) {
            if (! is_null($value = $model->{$this->foreignKey})) {
                $keys[] = $value;
            }
        }

        // If there are no keys that were not null we will just return an array with null
        // so this query wont fail plus returns zero results, which should be what the
        // developer expects to happen in this situation. Otherwise we'll sort them.
        if (count($keys) === 0) {
            return [null];
        }

        sort($keys);

        return array_values(array_unique($keys));
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

        $owner = $this->ownerKey;

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($owner)] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        foreach ($models as $model) {
            if (isset($dictionary[$model->{$foreign}])) {
                $model->setRelation($relation, $dictionary[$model->{$foreign}]);
            }
        }

        return $models;
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
        $ownerKey = $model instanceof Model ? $model->getAttribute($this->ownerKey) : $model;

        $this->child->setAttribute($this->foreignKey, $ownerKey);

        if ($model instanceof Model) {
            $this->child->setRelation($this->relation, $model);
        }

        return $this->child;
    }

    /**
     * Dissociate previously associated model from the given parent.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function dissociate()
    {
        $this->child->setAttribute($this->foreignKey, null);

        return $this->child->setRelation($this->relation, null);
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

        return $query->select($columns)->whereColumn(
            $this->getQualifiedForeignKey(), '=', $query->getModel()->getTable().'.'.$this->ownerKey
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
        $query->select($columns)->from(
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        return $query->whereColumn(
            $hash.'.'.$query->getModel()->getKeyName(), '=', $this->getQualifiedForeignKey()
        );
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
     * Make a new related instance for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function newRelatedInstanceFor(Model $parent)
    {
        return $this->related->newInstance();
    }

    /**
     * Get the foreign key of the relationship.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the fully qualified foreign key of the relationship.
     *
     * @return string
     */
    public function getQualifiedForeignKey()
    {
        return $this->child->getTable().'.'.$this->foreignKey;
    }

    /**
     * Get the associated key of the relationship.
     *
     * @return string
     */
    public function getOwnerKey()
    {
        return $this->ownerKey;
    }

    /**
     * Get the fully qualified associated key of the relationship.
     *
     * @return string
     */
    public function getQualifiedOwnerKeyName()
    {
        return $this->related->getTable().'.'.$this->ownerKey;
    }

    /**
     * Get the name of the relationship.
     *
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }
}
