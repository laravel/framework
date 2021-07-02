<?php

namespace Illuminate\Database\Eloquent\Concerns;

use LogicException;

trait PrunableQueries
{
    /**
     * Determines the prunable query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        throw new LogicException('The prunable method must be implemented on the model class.');
    }
}
