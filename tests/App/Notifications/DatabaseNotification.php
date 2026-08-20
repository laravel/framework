<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class DatabaseNotification extends Notification
{
    public function toDatabase($notifiable)
    {
        return new DatabaseMessage(['invoice_id' => 1]);
    }
}
