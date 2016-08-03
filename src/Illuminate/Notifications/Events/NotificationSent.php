<?php

namespace Illuminate\Notifications\Events;

use Illuminate\Notifications\Notification;

class NotificationSent
{
    /**
     * The notifiable entity who received the notification.
     *
     * @var mixed
     */
    public $notifiable;

    /**
     * The notification instance.
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $notification;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function __construct($notifiable, $notification)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
    }
}
