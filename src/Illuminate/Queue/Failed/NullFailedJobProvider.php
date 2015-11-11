<?php

namespace Illuminate\Queue\Failed;

class NullFailedJobProvider implements FailedJobProviderInterface
{
    /**
     * Log a failed job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @return void
     */
    public function log($connection, $queue, $payload)
    {
        //
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        return [];
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed  $id
     * @return array
     */
    public function find($id)
    {
        //
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed  $id
     * @return bool
     */
    public function forget($id)
    {
        return true;
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush()
    {
        //
    }
}
