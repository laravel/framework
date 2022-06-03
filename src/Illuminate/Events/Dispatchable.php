<?php

namespace Illuminate\Events;

use Illuminate\Support\Facades\Event;

trait Dispatchable
{
    /**
     * Dispatches an event of the host class.
     *
     * @param ...$args
     * @return array|null
     */
    public static function dispatch(...$args)
    {
        return Event::dispatch(new static(...$args));
    }
}
