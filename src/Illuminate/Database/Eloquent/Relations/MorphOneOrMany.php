<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class MorphOneOrMany extends HasOneOrMany
{
    /**
     * The foreign key type for the relationship.
     *
     * @var string
     */
    protected $morphType;

    /**
     * The class name of the parent model.
     *
     * @var string
     */
    protected $morphClass;

    /**
     * Create a new morph one or many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $localKey)
    {
        $this->morphType = $type;

        $this->morphClass = $parent->getMorphClass();

        parent::__construct($query, $parent, $id, $localKey);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            parent::addConstraints();

            $this->query->where($this->morphType, $this->morphClass);
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
        parent::addEagerConstraints($models);

        $this->query->where($this->morphType, $this->morphClass);
    }

    /**
     * Set the foreign ID and type for creating a related model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    protected function setForeignAttributesForCreate(Model $model)
    {
        $model->{$this->getForeignKeyName()} = $this->getParentKey();

        $model->{$this->getMorphType()} = $this->morphClass;
    }

    /**
     * Get the relationship query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns)->where(
            $query->qualifyColumn($this->getMorphType()), $this->morphClass
        );
    }

    /**
     * Get the foreign key "type" name.
     *
     * @return string
     */
    public function getQualifiedMorphType()
    {
        return $this->morphType;
    }

    /**
     * Get the plain morph type name without the table.
     *
     * @return string
     */
    public function getMorphType()
    {
        return last(explode('.', $this->morphType));
    }

    /**
     * Get the class name of the parent model.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }
}
