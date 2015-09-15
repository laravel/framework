<?php

namespace Illuminate\Bus;

class QueuingConfiguration
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string
     */
    public $queue;

    /**
     * The queue connection the job should be sent to.
     *
     * @var string
     */
    public $connection;

    /**
     * The seconds before the job should be made available.
     *
     * @var int
     */
    public $delay;

    /**
     * @param null|string $queue
     * @param null|string $connection
     * @param null|int $delay
     */
    public function __construct($queue = null, $connection = null, $delay = null)
    {
        $this->queue = $queue;
        $this->connection = $connection;
        $this->delay = $delay;
    }
}