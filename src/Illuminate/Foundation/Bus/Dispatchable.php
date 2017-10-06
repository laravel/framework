<?php

namespace Illuminate\Foundation\Bus;

trait Dispatchable
{
    /**
     * Dispatch the job with the given arguments.
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatch()
    {
        return new PendingDispatch(new static(...func_get_args()));
    }

    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @param  null|string  $chain_queue
     * @param  null|string  $chain_connection
     * @return \Illuminate\Foundation\Bus\PendingChain
     */
    public static function withChain(array $chain,string $chain_queue = null,string $chain_connection = null)
    {
        return new PendingChain(get_called_class(), $chain, $chain_queue, $chain_connection);
    }
}
