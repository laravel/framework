<?php

namespace Illuminate\Queue\Failed;

interface LimitableFailedJobProvider
{
    /**
     * Get a specific number of failed jobs.
     *
     * @param  int  $value
     * @return array
     */
    public function limit($value);
}
