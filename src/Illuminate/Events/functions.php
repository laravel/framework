<?php

namespace Illuminate\Events;

use Closure;

/**
 * Create a new queued Closure event listener.
 *
 * @param  \Closure  $closure
 * @return \Illuminate\Events\QueuedClosure
 */
function queueable(Closure $closure)
{
    return new QueuedClosure($closure);
}
