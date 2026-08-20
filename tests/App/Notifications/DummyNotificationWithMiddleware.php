<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Tests\Notifications\TestNotificationMiddleware;

class DummyNotificationWithMiddleware extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return 'mail';
    }

    public function middleware()
    {
        return [
            new TestNotificationMiddleware,
        ];
    }
}
