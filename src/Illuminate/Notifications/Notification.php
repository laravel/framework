<?php

namespace Illuminate\Notifications;

use Illuminate\Queue\SerializesModels;

class Notification
{
    use SerializesModels;

    /**
     * The unique identifier for the notification.
     *
     * @var string
     */
    public $id;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    /**
     * Sets the notification id.
     *
     * @param  string  $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
