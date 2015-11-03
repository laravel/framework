<?php

namespace Illuminate\Foundation\Bus;

use ArrayAccess;
use Illuminate\Contracts\Bus\Dispatcher;

trait DispatchesJobs
{
    /**
     * Dispatch a job to its appropriate handler.
     *
     * @param  mixed  $job
     * @return mixed
     */
    protected function dispatch($job)
    {
        return app(Dispatcher::class)->dispatch($job);
    }

    /**
     * Marshal a job and dispatch it to its appropriate handler.
     *
     * @param  mixed  $job
     * @param  array  $array
     * @return mixed
     */
    protected function dispatchFromArray($job, array $array)
    {
        return app(Dispatcher::class)->dispatchFromArray($job, $array);
    }

    /**
     * Marshal a job and dispatch it to its appropriate handler.
     *
     * @param  mixed  $job
     * @param  \ArrayAccess  $source
     * @param  array  $extras
     * @return mixed
     */
    protected function dispatchFrom($job, ArrayAccess $source, $extras = [])
    {
        return app(Dispatcher::class)->dispatchFrom($job, $source, $extras);
    }
}
