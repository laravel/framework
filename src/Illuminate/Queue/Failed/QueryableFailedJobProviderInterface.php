<?php

namespace Illuminate\Queue\Failed;

interface QueryableFailedJobProviderInterface extends FailedJobProviderInterface
{
    /**
     * Get a new query builder instance for the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function getQuery();
}
