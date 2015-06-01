<?php

namespace Illuminate\Bus;

trait Queueable
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string
     */
    public $queue;

    /**
     * The seconds before the job should be made available.
     *
     * @var int
     */
    public $delay;

    /**
     * Set the desired queue for the job.
     *
     * @param  string  $queue
     * @return $this
     */
    public function onQueue($queue)
    {
        $this->queue = $queue;

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
        $this->delay = $delay;

        return $this;
    }
}
