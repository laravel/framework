<?php

namespace Illuminate\Events;

use Closure;
use Illuminate\Queue\SerializableClosure;

class QueuedClosure
{
    /**
     * The underlying Closure.
     *
     * @var \Closure
     */
    public $closure;

    /**
     * Create a new queued closure event listener resolver.
     *
     * @param  \Closure  $closure
     * @return void
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * Resolve the actual event listener callback.
     *
     * @return \Closure
     */
    public function resolve()
    {
        return function (...$arguments) {
            dispatch(new CallQueuedListener(InvokeQueuedClosureForEvent::class, 'handle', [
                'closure' => new SerializableClosure($this->closure),
                'arguments' => $arguments,
            ]));
        };
    }
}
