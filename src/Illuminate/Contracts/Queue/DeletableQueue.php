<?php

namespace Illuminate\Contracts\Queue;

interface DeletableQueue
{
    /**
     * Delete a pending job from the queue.
     *
     * @param  string|null  $queue
     * @param  mixed  $id
     * @return bool
     */
    public function deletePending($queue, $id);
}
