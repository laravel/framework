<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class NotificationStub extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }
}
