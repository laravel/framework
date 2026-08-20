<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class DummyNotificationWithViaMutation extends Notification
{
    public $channelData = null;

    public function via($notifiable)
    {
        $this->channelData = $notifiable->routeConfig ?? 'default';

        return 'mail';
    }
}
