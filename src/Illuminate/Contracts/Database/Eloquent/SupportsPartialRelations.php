<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface SupportsPartialRelations
{
    /**
     * Indicate that the relation is a single result of a larger one-to-many relationship.
     *
     * @param  \Closure|string|null  $column
     * @param  string|null  $relation
     * @param  string  $relation
     * @return $this
     */
    public function ofMany($column = 'id', $aggregate = 'MAX', $relation = null);

    /**
     * Determine whether the relationship is a one-of-many relationship.
     *
     * @return bool
     */
    public function isOneOfMany();
}
