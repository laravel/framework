<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class HasOneThrough extends Relation
{
    /**
     * The distance parent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $farParent;

    /**
     * The foreign key from the Parent model to the primary key of the FarParent model.
     *
     * @var string
     */
    protected $parentForeignKey;

    /**
     * The foreign key of the Related model to the primary key of the Parent model.
     *
     * @var string
     */
    protected $relatedForeignKey;

    /**
     * Create a new has one through relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $farParent
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $parentForeignKey
     * @param  string  $relatedForeignKey
     */
    public function __construct(Builder $query, Model $farParent, Model $parent, $parentForeignKey, $relatedForeignKey)
    {
        $this->relatedForeignKey = $relatedForeignKey;
        $this->parentForeignKey = $parentForeignKey;
        $this->farParent = $farParent;

        parent::__construct($query, $parent);
    }

    /**
     * Get the far parent model of the relation.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getFarParent()
    {
        return $this->farParent;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $parentForeignKey = $this->parent->getTable().'.'.$this->parentForeignKey;

        $this->performJoin();

        if (static::$constraints) {
            $this->query->where($parentForeignKey, '=', $this->farParent->getKey());

            $this->query->whereNotNull($parentForeignKey);
        }
    }

    /**
     * Add the constraints for a relationship query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $this->performJoin($query);

        $query->select($columns);

        $parentForeignKey = $this->parent->getTable().'.'.$this->parentForeignKey;

        $this->query->where($parentForeignKey, '=', $this->farParent->getKey());

        return $this->query->whereNotNull($parentForeignKey);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    protected function performJoin(Builder $query = null)
    {
        $query = $query ?: $this->query;

        $relatedForeignKey = $this->related->getTable().'.'.$this->relatedForeignKey;

        $query->join($this->parent->getTable(), $this->parent->getQualifiedKeyName(), '=', $relatedForeignKey);

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
        $this->query->whereIn(
            $this->parent->getTable().'.'.$this->parentForeignKey,
            $this->getKeys($models, $this->farParent->getKeyName())
        );
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param  array  $models
     * @param  string  $relation
     * @return array
     */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, null);
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array  $models  Parent models
     * @param  \Illuminate\Database\Eloquent\Collection  $results  Related models
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        // Once we have the dictionary we can simply spin through the parent models to
        // link them up with their children using the keyed dictionary to make the
        // matching very convenient and easy work. Then we'll just return them.
        foreach ($models as $model) {
            $key = $model->getKey();

            if (isset($dictionary[$key])) {
                $value = $dictionary[$key];

                $model->setRelation($relation, $value);
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
            $dictionary[$result->{$this->parentForeignKey}] = $result;
        }

        return $dictionary;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->get()->first();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return mixed
     */
    public function get(array $columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
        $columns = $this->query->getQuery()->columns ? [] : $columns;

        $select = $this->getSelectColumns($columns);

        $builder = $this->query->applyScopes();

        $models = $builder->addSelect($select)->getModels();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return new Collection($models);
    }

    /**
     * Set the select clause for the relation query.
     *
     * @param  array  $columns
     * @return array
     */
    protected function getSelectColumns(array $columns = ['*'])
    {
        if ($columns == ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        return array_merge($columns, [
            $this->parent->getTable().'.'.$this->parentForeignKey,
        ]);
    }
}
