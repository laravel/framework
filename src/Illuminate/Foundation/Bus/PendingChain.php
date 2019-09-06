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
     * Indicates if the chain has already been dispatched.
     *
     * @var bool
     */
    public $dispatched = false;

    /**
     * Create a new PendingChain instance.
     *
     * @param  string  $class
     * @param  array  $chain
     * @return void
     */
    public function __construct($class, $chain)
    {
        $this->class = $class;
        $this->chain = $chain;
    }

    /**
     * Dispatch the job with the given arguments.
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function dispatch()
    {
        $this->dispatched = true;

        return (new PendingDispatch(
            new $this->class(...func_get_args())
        ))->chain($this->chain);
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        if (! $this->dispatched) {
            $this->dispatch();
        }
    }
}
