<?php

namespace Illuminate\Foundation\Bus;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Fluent;

trait Dispatchable
{
    /**
     * The data that was shared between previous jobs (if any).
     *
     * @var mixed
     */
    public $sharedData;

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
     * Dispatch the job with the given arguments if the given truth test passes.
     *
     * @param  bool  $boolean
     * @return \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent
     */
    public static function dispatchIf($boolean, ...$arguments)
    {
        return $boolean
            ? new PendingDispatch(new static(...$arguments))
            : new Fluent;
    }

    /**
     * Dispatch the job with the given arguments unless the given truth test passes.
     *
     * @param  bool  $boolean
     * @return \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent
     */
    public static function dispatchUnless($boolean, ...$arguments)
    {
        return ! $boolean
            ? new PendingDispatch(new static(...$arguments))
            : new Fluent;
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * Queuable jobs will be dispatched to the "sync" queue.
     *
     * @return mixed
     */
    public static function dispatchSync()
    {
        return app(Dispatcher::class)->dispatchSync(new static(...func_get_args()));
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @return mixed
     */
    public static function dispatchNow()
    {
        return app(Dispatcher::class)->dispatchNow(new static(...func_get_args()));
    }

    /**
     * Dispatch a command to its appropriate handler after the current process.
     *
     * @return mixed
     */
    public static function dispatchAfterResponse()
    {
        return app(Dispatcher::class)->dispatchAfterResponse(new static(...func_get_args()));
    }

    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @param  mixed  $sharedData
     * @return \Illuminate\Foundation\Bus\PendingChain
     */
    public static function withChain($chain, $sharedData = null)
    {
        return new PendingChain(static::class, $chain, $sharedData);
    }

    /**
     * Pass the shared data.
     *
     * @param  mixed  $sharedData
     * @return $this
     */
    public function sharedData($sharedData)
    {
        $this->sharedData = $sharedData;

        return $this;
    }
}
