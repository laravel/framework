<?php

namespace Illuminate\Contracts\Database\Eloquent;

interface Hydrator
{
    /**
     * Fill a new Eloquent model instance with raw attributes returned from the query builder.
     *
     * @param array $attributes
     * @return Hydratable
     */
    public function hydrate(array $attributes = []) : Hydratable;
}
