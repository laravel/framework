<?php

namespace Illuminate\Broadcasting;

use Illuminate\Contracts\Events\Dispatcher;

class PendingBroadcast
{
    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new pending broadcast instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Broadcast the event to everyone except the current user.
     *
     * @param  mixed  $event
     * @return void
     */
    public function toOthers($event)
    {
        $this->events->fire($event->dontBroadcastToCurrentUser());
    }
}
