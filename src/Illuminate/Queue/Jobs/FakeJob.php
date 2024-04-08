<?php

namespace Illuminate\Queue\Jobs;

use Illuminate\Support\Str;

class FakeJob extends Job
{
    /**
     * The number of seconds the released job was delayed.
     *
     * @var int
     */
    public $releaseDelay;

    /**
     * The exception the job failed with.
     *
     * @var \Throwable
     */
    public $failedWith;

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return once(fn () => (string) Str::uuid());
    }

    /**
     * Get the raw body of the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return '';
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->released = true;
        $this->releaseDelay = $delay;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->deleted = true;
    }

    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * @param  \Throwable|null  $exception
     * @return void
     */
    public function fail($exception = null)
    {
        $this->failed = true;
        $this->failedWith = $exception;
    }
}
