<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Job as JobContract;

/**
 * Interface that defines the contract of the InteractsWithQueue trait.
 */
interface InteractsWithQueueInterface
{
    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int;

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void;

    /**
     * Fail the job from the queue.
     *
     * @param  \Throwable|string|null  $exception
     * @return void
     */
    public function fail(\Throwable|string $exception = null): void;

    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release(int $delay = 0): void;

    /**
     * Set the base queue job instance.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return $this
     */
    public function setJob(JobContract $job): InteractsWithQueueInterface;
}
