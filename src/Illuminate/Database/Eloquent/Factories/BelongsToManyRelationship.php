<?php

namespace Illuminate\Database\Eloquent\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @implements ChildRelationship<TModel>
 */
class BelongsToManyRelationship implements ChildRelationship
{
    /**
     * The related factory instance.
     *
     * @var \Illuminate\Database\Eloquent\Factories\Factory<TModel>|\Illuminate\Support\Collection<int, TModel>|TModel|array<int, TModel>
     */
    protected $factory;

    /**
     * The pivot attributes / attribute resolver.
     *
     * @var callable|array
     */
    protected $pivot;

    /**
     * The relationship name.
     *
     * @var string
     */
    protected $relationship;

    /**
     * Whether relationships should be created in-memory without persisting.
     *
     * @var bool
     */
    protected $withInMemoryRelationships = false;

    /**
     * Create a new attached relationship definition.
     *
     * @param  \Illuminate\Database\Eloquent\Factories\Factory<TModel>|\Illuminate\Support\Collection<int, TModel>|TModel|array<int, TModel>  $factory
     * @param  callable|array  $pivot
     * @param  string  $relationship
     */
    public function __construct($factory, $pivot, $relationship)
    {
        $this->factory = $factory;
        $this->pivot = $pivot;
        $this->relationship = $relationship;
    }

    /**
     * Create the attached relationship for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function createFor(Model $model)
    {
        $factoryInstance = $this->factory instanceof Factory;

        if ($factoryInstance) {
            $relationship = $model->{$this->relationship}();
        }

        Collection::wrap($factoryInstance ? $this->factory->prependState($relationship->getQuery()->pendingAttributes)->create([], $model) : $this->factory)->each(function ($attachable) use ($model) {
            $model->{$this->relationship}()->attach(
                $attachable,
                is_callable($this->pivot) ? call_user_func($this->pivot, $model) : $this->pivot
            );
        });
    }

    /**
     * Make the attached relationship for the given model without persisting.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return TModel|\Illuminate\Database\Eloquent\Collection<array-key, TModel>
     */
    public function makeFor(Model $model)
    {
        $relationship = $model->{$this->relationship}();

        if ($this->factory instanceof Factory) {
            return $this->factory
                ->withInMemoryRelationships()
                ->prependState($relationship->getQuery()->pendingAttributes)
                ->make([], $model);
        }

        // If factory is already a collection/model, just return it wrapped
        return Collection::wrap($this->factory);
    }

    /**
     * Get the relationship name.
     *
     * @return string
     */
    public function getRelationship()
    {
        return $this->relationship;
    }

    /**
     * Indicate that relationships should be created in-memory without persisting.
     *
     * @param  bool  $state
     * @return $this
     */
    public function withInMemoryRelationships(bool $state = true)
    {
        $this->withInMemoryRelationships = $state;

        if ($this->factory instanceof Factory) {
            $this->factory = $this->factory->withInMemoryRelationships($state);
        }

        return $this;
    }

    /**
     * Specify the model instances to always use when creating relationships.
     *
     * @param  \Illuminate\Support\Collection  $recycle
     * @return $this
     */
    public function recycle($recycle)
    {
        if ($this->factory instanceof Factory) {
            $this->factory = $this->factory->recycle($recycle);
        }

        return $this;
    }
}
