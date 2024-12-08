<?php

namespace Illuminate\Broadcasting;

use Illuminate\Support\Facades\Broadcast;

trait InteractsWithSockets
{
    /**
     * The socket ID for the user that raised the event.
     *
     * @var string|null
     */
    public $socket;

    /**
     * Exclude the current user from receiving the broadcast.
     *
     * @return $this
     */
    public function dontBroadcastToCurrentUser()
    {
        return tap($this, fn () => $this->socket = Broadcast::socket());
    }

    /**
     * Broadcast the event to everyone.
     *
     * @return $this
     */
    public function broadcastToEveryone()
    {
        return tap($this, fn () => $this->socket = null);
    }
}
