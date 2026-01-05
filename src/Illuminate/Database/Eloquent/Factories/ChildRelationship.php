<?php

namespace Illuminate\Database\Eloquent\Factories;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface ChildRelationship
{
    /**
     * Create the child relationship for the given parent model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return void
     */
    public function createFor(Model $parent);

    /**
     * Make the child relationship for the given parent model without persisting.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @return TModel|\Illuminate\Database\Eloquent\Collection<array-key, TModel>|\Illuminate\Support\Collection
     */
    public function makeFor(Model $parent);

    /**
     * Get the relationship name.
     *
     * @return string
     */
    public function getRelationship();

    /**
     * Indicate that relationships should be created in-memory without persisting.
     *
     * @param  bool  $state
     * @return $this
     */
    public function withInMemoryRelationships(bool $state = true);

    /**
     * Specify the model instances to always use when creating relationships.
     *
     * @param  \Illuminate\Support\Collection  $recycle
     * @return $this
     */
    public function recycle($recycle);
}
