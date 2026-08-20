<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class BroadcastNotification extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }
}
