<?php

namespace Illuminate\Contracts\Broadcasting;

interface ShouldBroadcast
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array<int, \Illuminate\Broadcasting\Channel>|array<int, string>|string
     */
    public function broadcastOn();
}
