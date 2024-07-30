<?php

namespace Illuminate\Foundation\Bus\Exceptions;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use RuntimeException;

class JobDispatchedException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Process\ProcessResult  $result
     * @return void
     */
    public function __construct(public ShouldBeUnique $job)
    {
        parent::__construct(sprintf('The job %s is already dispatched.', $this->getIdentifier($job)), 409);
    }

    /**
     * Generate the lock key for the given job.
     *
     * @param  mixed  $job
     */
    protected function getIdentifier($job): string
    {
        $uniqueId = method_exists($job, 'uniqueId')
                    ? $job->uniqueId()
                    : ($job->uniqueId ?? '');

        return empty($uniqueId) ? get_class($job) : get_class($job) . "({$uniqueId})";
    }
}
