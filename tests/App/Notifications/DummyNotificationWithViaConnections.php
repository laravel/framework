<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DummyNotificationWithViaConnections extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->connection = 'redis';
        $this->queue = 'dummy';
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function viaConnections()
    {
        return [
            'database' => 'sync',
        ];
    }
}
