<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface PartialRelation
{
    /**
     * Wether the relation is a partial of a one-to-many relationship.
     *
     * @param  string|null $relation
     * @return $this
     */
    public function ofMany($relation = null);

    /**
     * Determines wether the relationship is one-of-many.
     *
     * @return bool
     */
    public function isOneOfMany();

    /**
     * Resolve the one-of-many query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function resolveOneOfManyQuery();
}
