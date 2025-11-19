<?php

namespace Illuminate\Queue\Concerns;

trait HasDefaultQueues
{
    /**
     * Set multiple default queues at once.
     *
     * @param  class-string  $class
     * @param  string  $queue
     * @return $this
     */
    public function defaultQueue($class, $queue)
    {
        $this->getQueueDefaults()->set($class, $queue);

        return $this;
    }

    /**
     * Set the default queues for the given classes.
     *
     * @param  array<class-string, string>  $queues
     * @return $this
     */
    public function defaultQueues($queues)
    {
        $this->getQueueDefaults()->setMany($queues);

        return $this;
    }

    /**
     * Get the default queue for a given queueable instance.
     *
     * @param  object  $queueable
     * @return string|null
     */
    public function getDefaultQueue($queueable)
    {
        return $this->getQueueDefaults()->get($queueable);
    }

    /**
     * Get the queue defaults instance.
     *
     * @return \Illuminate\Queue\QueueDefaults
     */
    abstract protected function getQueueDefaults();
}
