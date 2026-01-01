<?php

namespace Illuminate\Queue\Concerns;

trait ResolvesQueueRoutes
{
    /**
     * Resolve the default route name for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function resolveQueueRoute($queueable)
    {
        return $this->queueRoutes()->get($queueable);
    }

    /**
     * Get the queue routes instance.
     *
     * @return \Illuminate\Queue\QueueRoutes
     */
    abstract protected function queueRoutes();
}
