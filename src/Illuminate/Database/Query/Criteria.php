<?php

namespace Illuminate\Database\Query;

use Illuminate\Contracts\Database\Query\Builder;

interface Criteria
{
    /**
     * Apply the criteria to a given query builder.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder  $builder
     * @return \Illuminate\Contracts\Database\Query\Builder
     */
    public function apply(Builder $builder);
}
