<?php

namespace Illuminate\Notifications\Events;

use Illuminate\Notifications\Channels\Notification;

class NotificationSent
{
    /**
     * The notification instance.
     *
     * @var \Illuminate\Notifications\Channels\Notification
     */
    public $notification;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Notifications\Channels\Notification  $notification
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }
}
