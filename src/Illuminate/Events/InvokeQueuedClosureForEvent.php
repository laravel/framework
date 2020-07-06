<?php

namespace Illuminate\Events;

class InvokeQueuedClosureForEvent
{
    /**
     * Handle the event.
     *
     * @param  \Closure  $closure
     * @param  array  $arguments
     * @return void
     */
    public function handle($closure, array $arguments)
    {
        call_user_func($closure->getClosure(), ...$arguments);
    }
}
