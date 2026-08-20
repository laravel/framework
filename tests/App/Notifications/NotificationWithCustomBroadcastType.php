<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class NotificationWithCustomBroadcastType extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }

    public function broadcastType()
    {
        return 'custom.type';
    }
}
