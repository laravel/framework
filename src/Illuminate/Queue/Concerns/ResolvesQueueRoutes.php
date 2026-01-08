<?php

namespace Illuminate\Queue\Concerns;

use Illuminate\Container\Container;
use Illuminate\Queue\QueueRoutes;

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
     * Get the queue routes manager instance.
     *
     * @return \Illuminate\Queue\QueueRoutes
     */
    protected function queueRoutes()
    {
        $container = Container::getInstance();

        return $container->bound('queue.routes')
            ? $container->make('queue.routes')
            : new QueueRoutes;
    }
}
