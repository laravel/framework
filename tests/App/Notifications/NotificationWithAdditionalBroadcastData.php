<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class NotificationWithAdditionalBroadcastData extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }

    public function broadcastWith()
    {
        return ['id' => 1, 'type' => 'custom', 'additional' => 'custom'];
    }
}
