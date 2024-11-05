<?php

namespace Illuminate\Queue\Failed;

interface LimitableFailedJobProvider
{
    /**
     * Count the failed jobs.
     *
     * @param  int  $value
     * @return array
     */
    public function limit($value);
}
