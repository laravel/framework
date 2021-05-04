<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface PartialRelation
{
    /**
     * Wether the relation is a partial of a one-to-many relationship.
     *
     * @param  boolean $ofMany
     * @return $this
     */
    public function ofMany(bool $ofMany = true);

    /**
     * Determines wether the relationship is one-of-many.
     *
     * @return boolean
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
