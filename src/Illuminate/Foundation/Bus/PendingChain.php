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
    public $chain_connection=null;

    /**
     * The name of the queue the chained jobs should be sent to if not set on job.
     *
     * @var string|null
     */
    public $chain_queue=null;

    /**
     * Create a new PendingChain instance.
     *
     * @param      $class
     * @param      $chain
     * @param null $chain_queue
     * @param null $chain_connection
     * @return void
     */
    public function __construct($class, $chain, $chain_queue=null, $chain_connection=null)
    {
        $this->class = $class;
        $this->chain = $chain;
        $this->chain_queue = $chain_queue;
        $this->chain_connection = $chain_connection;
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
        ))->chain($this->chain, $this->chain_queue, $this->chain_connection);
    }
}
