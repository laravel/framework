<?php

namespace Illuminate\Queue\Events;

use Illuminate\Contracts\Queue\Queue;

class JobQueued
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job class to dispatch.
     *
     * @var
     */
    public $job;

    /**
     * The optional queue name that is wanted depending on queue driver.
     *
     * @var null|string
     */
    public $queueName;

    /**
     * Depending on queue driver, this could be the reference to the concrete job.
     *
     * @var mixed
     */
    public $queueReference;

    /**
     * Create a new event instance.
     *
     * @param Queue   $queue
     * @param         $job
     * @param mixed   $queueReference
     *
     * @internal param string $connectionName
     */
    public function __construct(Queue $queue, $job, $queueReference = null)
    {
        $this->connectionName = $queue->getConnectionName();
        $this->job = $job;
        $this->queueName = $this->extractQueueName($queue, $job);
        $this->queueReference = $queueReference;
    }

    /**
     * @param Queue $queue
     * @param       $job
     *
     * @return string|null The optional queue name
     */
    private function extractQueueName(Queue $queue, $job)
    {
        if (method_exists($queue, 'getQueue')) {
            return $queue->getQueue($job->queue);
        }

        return null;
    }
}