<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Notification;

class NotificationWithCustomBroadcastChannel extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }

    public function broadcastOn()
    {
        return [new PrivateChannel('custom-channel')];
    }
}
