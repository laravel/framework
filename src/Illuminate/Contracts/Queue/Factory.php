<?php

namespace Illuminate\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null);

    /**
     * Set queue depth threshold for monitoring.
     *
     * @param  string  $queue
     * @param  int  $maxSize
     * @return $this
     */
    public function setDepthThreshold($queue, $maxSize);

    /**
     * Get queue depth threshold.
     *
     * @param  string  $queue
     * @return int|null
     */
    public function getDepthThreshold($queue);

    /**
     * Check queue depth and dispatch event if threshold exceeded.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return bool
     */
    public function checkDepthAndNotify($connection, $queue);
}
