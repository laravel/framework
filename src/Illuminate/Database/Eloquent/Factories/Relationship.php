<?php

namespace Illuminate\Database\Eloquent\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class Relationship implements ChildRelationship
{
    /**
     * The related factory instance.
     *
     * @var \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected $factory;

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
     * Create a new child relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Factories\Factory  $factory
     * @param  string  $relationship
     */
    public function __construct(Factory $factory, $relationship)
    {
        $this->factory = $factory;
        $this->relationship = $relationship;
    }

    /**
     * Create the child relationship for the given parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return void
     */
    public function createFor(Model $parent)
    {
        $relationship = $parent->{$this->relationship}();

        if ($relationship instanceof MorphOneOrMany) {
            $this->factory->state([
                $relationship->getMorphType() => $relationship->getMorphClass(),
                $relationship->getForeignKeyName() => $relationship->getParentKey(),
            ])->prependState($relationship->getQuery()->pendingAttributes)->create([], $parent);
        } elseif ($relationship instanceof HasOneOrMany) {
            $this->factory->state([
                $relationship->getForeignKeyName() => $relationship->getParentKey(),
            ])->prependState($relationship->getQuery()->pendingAttributes)->create([], $parent);
        } elseif ($relationship instanceof BelongsToMany) {
            $relationship->attach(
                $this->factory->prependState($relationship->getQuery()->pendingAttributes)->create([], $parent)
            );
        }
    }

    /**
     * Make the child relationship for the given parent model without persisting.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     */
    public function makeFor(Model $parent)
    {
        $relationship = $parent->{$this->relationship}();

        if ($relationship instanceof MorphOneOrMany) {
            return $this->factory
                ->withInMemoryRelationships()
                ->prependState($relationship->getQuery()->pendingAttributes)
                ->make([], $parent);
        } elseif ($relationship instanceof HasOneOrMany) {
            return $this->factory
                ->withInMemoryRelationships()
                ->prependState($relationship->getQuery()->pendingAttributes)
                ->make([], $parent);
        } elseif ($relationship instanceof BelongsToMany) {
            return $this->factory
                ->withInMemoryRelationships()
                ->prependState($relationship->getQuery()->pendingAttributes)
                ->make([], $parent);
        }

        return $this->factory->withInMemoryRelationships()->make([], $parent);
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
        $this->factory = $this->factory->withInMemoryRelationships($state);

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
        $this->factory = $this->factory->recycle($recycle);

        return $this;
    }
}
