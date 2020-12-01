<?php

namespace Illuminate\Queue\Secondary;

interface SecondaryQueueProviderInterface
{
    /**
     * Push a job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $job
     * @param  \DateTimeInterface|\DateInterval|int|null  $delay
     * @param  \Throwable  $exception
     * @return string|int|null
     */
    public function push($connection, $queue, $job, $delay, $exception);

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all();

    /**
     * Delete a single job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id);

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush();
}
