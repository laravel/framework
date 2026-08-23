<?php

namespace Illuminate\Contracts\Queue;

interface PreparesForRetry
{
    /**
     * Run preparation logic before the failed job is retried. Return false to leave the job in the failed jobs table.
     *
     * @return bool|void
     */
    public function prepareForRetry();
}
