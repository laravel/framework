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
     * The notification message instance.
     *
     * @var \Illuminate\Notifications\Message
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Message  $message
     * @return void
     */
    public function __construct($notifiable, $message)
    {
        $this->notifiable = $notifiable;
        $this->message = $message;
    }
}
