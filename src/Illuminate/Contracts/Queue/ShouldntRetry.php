<?php

namespace Illuminate\Contracts\Queue;

/**
 * Marks an exception that should cause a job to fail immediately.
 *
 * When a job throws an exception implementing this contract, the job skips any
 * retries it has remaining and is failed immediately, regardless of the number
 * of attempts or the "retry until" timestamp configured for the job.
 */
interface ShouldntRetry
{
    //
}
