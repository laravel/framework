<?php

namespace Illuminate\Bus;

trait Queueable
{
    /**
     * The queuing configuration
     *
     * @var QueuingConfiguration
     */
    public $queue;

    /**
     * Set the desired queue for the job.
     *
     * @param  string  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->getQueue()->queue = $queue;

        return $this;
    }

    /**
     * Set the desired queue connection for the job
     *
     * @param $connection
     * @return $this
     */
    public function onConnection($connection)
    {
        $this->getQueue()->connection = $connection;

        return $this;
    }

    /**
     * Set the desired delay for the job.
     *
     * @param  int  $delay
     * @return $this
     */
    public function delay($delay)
    {
        $this->getQueue()->delay = $delay;

        return $this;
    }

    /**
     * @return QueuingConfiguration
     */
    public function getQueue()
    {
        if(is_null($this->queue)) {
            $this->queue = new QueuingConfiguration();
        }
        return $this->queue;
    }
}
