<?php

namespace Illuminate\Contracts\Broadcasting;

interface HasBroadcastChannel
{
    /**
     * Get the broadcast channel name that is associated with the given entity.
     *
     * @return string
     */
    public function broadcastChannelName();
}
