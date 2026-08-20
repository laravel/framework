<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class NotificationWithFalsyShouldSendStub extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function shouldSend($notifiable, $channel)
    {
        return false;
    }
}
