<?php

namespace Illuminate\Notifications;

use Illuminate\Queue\SerializesModels;

class Notification
{
    use SerializesModels;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
