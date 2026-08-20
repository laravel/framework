<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NotificationBroadcastOnSyncConnection extends Notification
{
    public function toArray($notifiable)
    {
        return ['invoice_id' => 1];
    }

    public function toBroadcast()
    {
        return (new BroadcastMessage([]))->onConnection('sync');
    }
}
