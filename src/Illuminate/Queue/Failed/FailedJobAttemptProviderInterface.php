<?php

namespace Illuminate\Queue\Failed;

interface FailedJobAttemptProviderInterface
{
    /**
     * Log a failed job attempt into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  string  $payload
     * @param  Exception  $exception
     * @return void
     */
    public function log($connection, $queue, $payload, $exception);

    /**
     * Flush all of the failed job attempts from storage.
     *
     * @return void
     */
    public function flush();
}
