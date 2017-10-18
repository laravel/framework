<?php

namespace Illuminate\Foundation\Bus;

class PendingChain
{
    /**
     * The class name of the job being dispatched.
     *
     * @var string
     */
    public $class;

    /**
     * The jobs to be chained.
     *
     * @var array
     */
    public $chain;

    /**
     * The name of the connection the jobs should be sent to if not set on job.
     *
     * @var string|null
     */
    public $chainConnection = null;

    /**
     * The name of the queue the chained jobs should be sent to if not set on job.
     *
     * @var string|null
     */
    public $chain_queue = null;

    /**
     * Create a new PendingChain instance.
     *
     * @param      $class
     * @param      $chain
     * @param null|string $chainQueue
     * @param null|string $chainConnection
     * @return void
     */
    public function __construct($class, $chain, $chainQueue = null, $chainConnection = null)
    {
        $this->class = $class;
        $this->chain = $chain;
        $this->chainQueue = $chainQueue;
        $this->chainConnection = $chainConnection;
    }

    /**
     * Dispatch the job with the given arguments.
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function dispatch()
    {
        return (new PendingDispatch(
            new $this->class(...func_get_args())
        ))->chain($this->chain, $this->chainQueue, $this->chainConnection);
    }
}
