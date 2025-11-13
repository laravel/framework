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
     * Pause a queue by name.
     *
     * @param  string  $queue
     * @param  int  $ttl
     * @return void
     */
    public function pause($queue, $ttl = 86400);

    /**
     * Resume a paused queue by name.
     *
     * @param  string  $queue
     * @return void
     */
    public function resume($queue);

    /**
     * Determine if a queue is paused.
     *
     * @param  string  $queue
     * @return bool
     */
    public function isPaused($queue);

    /**
     * Get all paused queues.
     *
     * @return array
     */
    public function getPausedQueues();
}
