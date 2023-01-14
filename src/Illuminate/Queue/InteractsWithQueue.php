<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Job as JobContract;
use Throwable;

trait InteractsWithQueue
{
    /**
     * The underlying queue job instance.
     *
     * @var \Illuminate\Contracts\Queue\Job|null
     */
    public $job;

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return $this->job ? $this->job->attempts() : 1;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void
    {
        if ($this->job) {
            $this->job->delete();
        }
    }

    /**
     * Fail the job from the queue.
     *
     * @param  \Throwable|string|null  $exception
     * @return void
     */
    public function fail(Throwable|string $exception = null): void
    {
        if (is_string($exception)) {
            $exception = new ManuallyFailedException($exception);
        }

        if ($this->job) {
            $this->job->fail($exception);
        }
    }

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release(int $delay = 0): void
    {
        if ($this->job) {
            $this->job->release($delay);
        }
    }

    /**
     * Set the base queue job instance.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return $this
     */
    public function setJob(JobContract $job): InteractsWithQueueInterface
    {
        $this->job = $job;

        return $this;
    }
}
