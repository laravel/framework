<?php

namespace Illuminate\Database\Eloquent\Factory;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

trait BuildsRelationships
{
    /**
     * The current batch no.
     *
     * @var int
     */
    protected $currentBatch = 0;

    /**
     * Requested relations.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Load a RelationRequest onto current FactoryBuilder.
     *
     * @param  \Illuminate\Database\Eloquent\Factory\RelationRequest  $request
     * @return $this
     */
    public function loadRelation(RelationRequest $request)
    {
        $factory = $this->buildFactoryForRequest($request);

        // Recursively create factories until no further nesting.
        if ($request->hasNesting()) {
            $factory->with($request->createNestedRequest());
        } // Apply the request onto the newly created factory.
        else {
            $factory
                ->fill($request->attributes)
                ->presets($request->presets)
                ->states($request->states)
                ->when($request->amount, function ($factory, $amount) {
                    $factory->times($amount);
                })
                ->when($request->builder, function ($factory, $builder) {
                    $factory->tap($builder);
                });
        }

        return $this;
    }

    /**
     * Build a factory for given RelationRequest.
     *
     * @param  \Illuminate\Database\Eloquent\Factory\RelationRequest  $request
     * @return static
     */
    protected function buildFactoryForRequest($request)
    {
        $relation = $request->getRelationName();
        $batch = $request->getBatch();

        return data_get($this->relations, "{$relation}.{$batch}", function () use ($request, $relation, $batch) {
            return tap(app(Factory::class)->of($request->getRelatedClass()), function ($factory) use ($relation, $batch) {
                $this->relations[$relation][$batch] = $factory;
            });
        });
    }

    /**
     * Create all requested BelongsTo relations.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $child
     * @return void
     */
    protected function createBelongsTo($child)
    {
        collect($this->relations)
            ->filter($this->relationTypeIs(BelongsTo::class))
            ->each(function ($batches, $relation) use ($child) {
                foreach (array_slice($batches, 0, 1) as $factory) {
                    $parent = $this->collectModel($factory->inheritConnection($this)->create());
                    $child->$relation()->associate($parent);
                }
            });
    }

    /**
     * Create all requested BelongsToMany relations.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $sibling
     * @return void
     */
    protected function createBelongsToMany($sibling)
    {
        collect($this->relations)
            ->filter($this->relationTypeIs(BelongsToMany::class))
            ->each(function ($batches, $relation) use ($sibling) {
                foreach ($batches as $factory) {
                    $models = $this->collect($factory->inheritConnection($this)->create());
                    $models->each(function ($model) use ($sibling, $relation, $factory) {
                        $sibling->$relation()->save($model, $this->mergeAndExpandAttributes($factory->pivotAttributes));
                    });
                }
            });
    }

    /**
     * Create all requested HasMany relations.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return void
     */
    protected function createHasMany($parent)
    {
        collect($this->relations)
            ->filter($this->relationTypeIs(HasOneOrMany::class))
            ->each(function ($batches, $relation) use ($parent) {
                foreach ($batches as $factory) {
                    // In case of morphOne / morphMany we'll need to set the morph type as well.
                    if (($morphRelation = $this->newRelation($relation)) instanceof MorphOneOrMany) {
                        $factory->fill([
                            $morphRelation->getMorphType() => (new $this->class)->getMorphClass(),
                        ]);
                    }

                    $factory->inheritConnection($this)->create([
                        $parent->$relation()->getForeignKeyName() => $parent->$relation()->getParentKey(),
                    ]);
                }
            });
    }

    /**
     * Get closure that checks for a given relation-type.
     *
     * @param  string  $relationType
     * @return \Closure
     */
    protected function relationTypeIs($relationType)
    {
        return function ($batches, $relation) use ($relationType) {
            return $this->newRelation($relation) instanceof $relationType;
        };
    }

    /**
     * Create a new instance of the relationship.
     *
     * @param  string  $relationName
     * @return \Illuminate\Database\Eloquent\Relations\Relation;
     */
    protected function newRelation($relationName)
    {
        return (new $this->class)->$relationName();
    }

    /**
     * Inherit connection from a parent factory.
     *
     * @param  \Illuminate\Database\Eloquent\FactoryBuilder  $factory
     * @return $this
     */
    protected function inheritConnection($factory)
    {
        if ($this->connection === null && (new $this->class)->getConnectionName() === null) {
            return $this->connection($factory->connection);
        }

        return $this;
    }

    /**
     * Create a new batch of relations.
     *
     * @return $this
     */
    protected function newBatch()
    {
        $this->currentBatch++;

        return $this;
    }
}
