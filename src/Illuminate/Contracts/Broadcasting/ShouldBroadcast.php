<?php

namespace Illuminate\Contracts\Broadcasting;

interface ShouldBroadcast
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn();
}
