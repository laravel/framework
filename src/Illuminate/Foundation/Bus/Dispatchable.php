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
     * @param  null|string  $chainQueue
     * @param  null|string  $chainConnection
     * @return \Illuminate\Foundation\Bus\PendingChain
     */
    public static function withChain(array $chain, string $chainQueue = null, string $chainConnection = null)
    {
        return new PendingChain(get_called_class(), $chain, $chainQueue, $chainConnection);
    }
}
