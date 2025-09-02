<?php

namespace Illuminate\Events;

use Illuminate\Support\Collection;

class InvokeQueuedClosure
{
    /**
     * Handle the event.
     *
     * @param  \Laravel\SerializableClosure\SerializableClosure  $closure
     * @return void
     */
    public function handle($closure, array $arguments)
    {
        call_user_func($closure->getClosure(), ...$arguments);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Laravel\SerializableClosure\SerializableClosure  $closure
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed($closure, array $arguments, array $catchCallbacks, $exception)
    {
        $arguments[] = $exception;

        (new Collection($catchCallbacks))->each->__invoke(...$arguments);
    }
}
