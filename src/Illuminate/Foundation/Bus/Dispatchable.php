<?php

namespace Illuminate\Foundation\Bus;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Fluent;

trait Dispatchable
{
    /**
     * Dispatch the job with the given arguments.
     *
     * @param  mixed  ...$arguments
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public static function dispatch(...$arguments)
    {
        return new PendingDispatch(new static(...$arguments));
    }

    /**
     * Dispatch the job with the given arguments if the given truth test passes.
     *
     * @param  bool|\Closure  $boolean
     * @param  mixed  ...$arguments
     * @return \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent
     */
    public static function dispatchIf($boolean, ...$arguments)
    {
        if ($boolean instanceof Closure) {
            $dispatchable = new static(...$arguments);

            return value($boolean, $dispatchable)
                ? new PendingDispatch($dispatchable)
                : new Fluent;
        }

        return value($boolean)
            ? new PendingDispatch(new static(...$arguments))
            : new Fluent;
    }

    /**
     * Dispatch the job with the given arguments unless the job is already queued.
     *
     * @see \Illuminate\Queue\Middleware\Debounced::handle()
     */
    // todo - add \DateTimeInterface|\DateInterval as accepted types for wait
    public static function dispatchDebounced(int $wait, ...$arguments): PendingDispatch
    {
        $dispatchable = new static(...$arguments);

        if (!in_array(Queueable::class, class_uses_recursive(static::class), true)) {
            throw new \InvalidArgumentException(
                'Debounced jobs must use the ' . class_basename(Queueable::class) .  ' trait.'
            );
        }

//        // todo - what if it's set later on the PendingDispatch
//        // ::dispatch($podcast)->onConnection('sqs')->onQueue('processing');
//        if (($dispatchable->connection ?? config('queue.default')) === 'sync') {
//            if (config('app.debug') && app()->isLocal()) {
//                throw new \LogicException('Debounced jobs must not run in');
//            }
//
//            return self::dispatchSync(...$arguments);
//        }

        $key = 'debounced.' . get_class($dispatchable);

        if ($dispatchable instanceof ShouldBeUnique && method_exists($dispatchable, 'uniqueId')) {
            // use the uniqueId to debounce by if defined
            $key .= '.uniqueBy.' . $dispatchable->uniqueId();
        }

        cache()->forever($key, now()->addSeconds($wait)->toISOString());
        cache()->increment($key . '.count');

        return (new PendingDispatch($dispatchable))->delay($wait);
    }

    /**
     * Dispatch the job with the given arguments unless the given truth test passes.
     *
     * @param  bool|\Closure  $boolean
     * @param  mixed  ...$arguments
     * @return \Illuminate\Foundation\Bus\PendingDispatch|\Illuminate\Support\Fluent
     */
    public static function dispatchUnless($boolean, ...$arguments)
    {
        if ($boolean instanceof Closure) {
            $dispatchable = new static(...$arguments);

            return ! value($boolean, $dispatchable)
                ? new PendingDispatch($dispatchable)
                : new Fluent;
        }

        return ! value($boolean)
            ? new PendingDispatch(new static(...$arguments))
            : new Fluent;
    }

    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * Queueable jobs will be dispatched to the "sync" queue.
     *
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public static function dispatchSync(...$arguments)
    {
        return app(Dispatcher::class)->dispatchSync(new static(...$arguments));
    }

    /**
     * Dispatch a command to its appropriate handler after the current process.
     *
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public static function dispatchAfterResponse(...$arguments)
    {
        return self::dispatch(...$arguments)->afterResponse();
    }

    /**
     * Set the jobs that should run if this job is successful.
     *
     * @param  array  $chain
     * @return \Illuminate\Foundation\Bus\PendingChain
     */
    public static function withChain($chain)
    {
        return new PendingChain(static::class, $chain);
    }
}
