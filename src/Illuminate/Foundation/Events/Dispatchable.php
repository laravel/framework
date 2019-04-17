<?php

namespace Illuminate\Foundation\Events;

use Laravel;

trait Dispatchable
{
    /**
     * Dispatch the event with the given arguments.
     *
     * @return void
     */
    public static function dispatch()
    {
        return Laravel::event(new static(...func_get_args()));
    }

    /**
     * Broadcast the event with the given arguments.
     *
     * @return \Illuminate\Broadcasting\PendingBroadcast
     */
    public static function broadcast()
    {
        return Laravel::broadcast(new static(...func_get_args()));
    }
}
