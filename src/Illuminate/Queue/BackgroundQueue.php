<?php

namespace Illuminate\Queue;

use Illuminate\Support\Facades\Concurrency;

class BackgroundQueue extends SyncQueue
{
    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     *
     * @throws \Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
        $connection = $this->connectionName;

        Concurrency::driver('process')->defer(
            fn () => \Illuminate\Support\Facades\Queue::connection('sync')
                ->setConnectionName($connection)
                ->push($job, $data, $queue)
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $connection = $this->connectionName;

        Concurrency::driver('process')->defer(
            fn () => \Illuminate\Support\Facades\Queue::connection('sync')
                ->setConnectionName($connection)
                ->pushRaw($payload, $queue, $options)
        );
    }
}
