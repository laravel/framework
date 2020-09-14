<?php

namespace Illuminate\Contracts\Queue;

interface ClearableQueue
{
    /**
     * Clear all jobs from the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function clear($queue);
}
