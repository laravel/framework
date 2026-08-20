<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Tests\Integration\Notifications\AnotherTestCustomChannel;
use Illuminate\Tests\Integration\Notifications\TestCustomChannel;

class TestMailNotificationForAnonymousNotifiable extends Notification
{
    public function via($notifiable)
    {
        return [TestCustomChannel::class, AnotherTestCustomChannel::class];
    }
}
