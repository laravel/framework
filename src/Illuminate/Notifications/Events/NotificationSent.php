<?php

namespace Illuminate\Notifications\Events;

use Illuminate\Notifications\Transports\Notification;

class NotificationSent
{
    /**
     * The notification instance.
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $notification;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }
}
