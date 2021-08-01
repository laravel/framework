<?php

namespace Illuminate\Queue\Events;

class QueueBusy
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connection;

    /**
     * The queue name.
     *
     * @var string
     */
    public $queue;

    /**
     * The size of the queue.
     *
     * @var int
     */
    public $size;

    /**
     * Create a new event instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $size
     * @return void
     */
    public function __construct($connection, $queue, $size)
    {
        $this->connection = $connection;
        $this->queue = $queue;
        $this->size = $size;
    }
}
