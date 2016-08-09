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
     * The event instance.
     *
     * @var mixed
     */
    protected $event;

    /**
     * Create a new pending broadcast instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param
     */
    public function __construct(Dispatcher $events, $event)
    {
        $this->event = $event;
        $this->events = $events;
    }

    /**
     * Broadcast the event to all listeners.
     *
     * @return void
     */
    public function all()
    {
        $this->events->fire($this->event->broadcastToEveryone());
    }

    /**
     * Broadcast the event to everyone except the current user.
     *
     * @return void
     */
    public function others()
    {
        $this->events->fire($this->event->dontBroadcastToCurrentUser());
    }
}
