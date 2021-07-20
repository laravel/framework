<?php

namespace Illuminate\Broadcasting;

trait InteractsWithBroadcaster
{
    /**
     * The Broadcaster to use to broadcast the event.
     *
     * @var string|null
     */
    public $broadcaster;

    /**
     * Broadcast the event using a specific broadcaster.
     *
     * @param  string|null  $connection
     * @return $this
     */
    public function withBroadcaster($connection = null)
    {
        $this->broadcaster = $connection;

        return $this;
    }
}
