<?php

namespace Illuminate\Contracts\Database\Eloquent;

use Closure;

interface PartialRelation
{
    /**
     * Wether the relation is a partial of a one-to-many relationship.
     *
     * @param  Closure|string|null $column
     * @param  string|null $relation
     * @return $this
     */
    public function ofMany($column = 'id', $aggregate = 'MAX', $relation = null);

    /**
     * Determines wether the relationship is one-of-many.
     *
     * @return bool
     */
    public function isOneOfMany();
}
