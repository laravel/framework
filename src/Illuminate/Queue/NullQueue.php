<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;

class NullQueue extends Queue implements QueueContract
{
    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return 0;
    }

    /**
     * Get the number of pending jobs (none for null driver).
     *
     * @param  string|null  $queue
     * @return int
     */
    public function sizePending($queue = null)
    {
        return 0;
    }

    /**
     * Get the number of delayed jobs (none for null driver).
     *
     * @param  string|null  $queue
     * @return int
     */
    public function sizeDelayed($queue = null)
    {
        return 0;
    }

    /**
     * Get the number of reserved jobs (none for null driver).
     *
     * @param  string|null  $queue
     * @return int
     */
    public function sizeReserved($queue = null)
    {
        return 0;
    }

    /**
     * Get the timestamp of the oldest pending job (not supported for null driver).
     *
     * @param  string|null  $queue
     * @return int|null
     */
    public function oldestPending($queue = null)
    {
        return null;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        //
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
        //
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        //
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        //
    }
}
