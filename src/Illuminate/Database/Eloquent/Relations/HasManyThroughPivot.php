<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class HasManyThroughPivot extends Relation
{
    /**
     * The model of the relationship to go through.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $throughParent;

    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $localKey;

    /**
     * The related foreign key of the foreign model.
     *
     * @var string
     */
    protected $foreignRelatedKey;

    /**
     * The related foreign key of the far foreign model.
     *
     * @var string
     */
    protected $farForeignRelatedKey;

    /**
     * The related key of the far foreign model.
     *
     * @var string
     */
    protected $relatedKey;

    public function __construct(Builder $query, Model $parent, Model $throughParent, $localKey, $foreignRelatedKey, $farForeignRelatedKey, $relatedKey)
    {
        $this->throughParent = $throughParent;
        $this->localKey = $localKey;
        $this->foreignRelatedKey = $foreignRelatedKey;
        $this->farForeignRelatedKey = $farForeignRelatedKey;
        $this->relatedKey = $relatedKey;

        parent::__construct($query, $parent);
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $query = $this->getRelationQuery();

        $query->join($this->throughParent->getTable(), function ($join) {
            return $join->on(
                $this->related->getTable().'.'.$this->getRelatedKey(),
                '=',
                $this->throughParent->getTable().'.'.$this->getForeignRelatedKey(),
            );
        });

        $query->join($this->parent->getTable(), function ($join) {
            return $join->on(
                $this->throughParent->getTable().'.'.$this->getFarForeignRelatedKey(),
                '=',
                $this->parent->getTable().'.'.$this->getLocalKey(),
            );
        });
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  array  $models  An array of parent models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this->getRelationQuery()->whereIn(
            $this->parent->getTable().'.'.$this->getLocalKey(),
            $this->getKeys($models, $this->getLocalKey())
        );
    }

    /**
     * Initialize the relation on a set of models.
     *
     * It Initialises the empty relationship on every
     * parent model, so that it can be filled afterwards.
     *
     * @param  array  $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation(
                $relation,
                $this->related->newCollection()
            );
        }

        return $models;
    }

    /**
     * Get the results of the relationship.
     *
     * Used when we access the relationship via dynamic property
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->get($this->related->getTable().'.*');
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        if ($columns == ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        return $this->query->get($columns);
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation(
                $relation,
                new Collection($results),
            );
        }

        return $models;
    }

    /**
     * Get the local key for the relationship.
     *
     * @return string
     */
    public function getLocalKey()
    {
        return $this->localKey;
    }

    /**
     * Get the far foregin related key for the relationship.
     *
     * @return string
     */
    public function getForeignRelatedKey()
    {
        return $this->foreignRelatedKey;
    }

    /**
     * Get the far foregin related key on the given relationship and
     * default to the related model foreign key name.
     *
     * @return string
     */
    public function getFarForeignRelatedKey()
    {
        return $this->farForeignRelatedKey ?: $this->related->getForeignKey();
    }

    /**
     * Get the related key for the relationship and default to the
     * model's primary key.
     *
     * @return string
     */
    public function getRelatedKey()
    {
        return $this->relatedKey ?: $this->related->getKeyName();
    }
}
