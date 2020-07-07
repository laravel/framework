<?php

namespace Illuminate\Events;

use Closure;

function queueable(Closure $closure)
{
    return new QueuedClosure($closure);
}
