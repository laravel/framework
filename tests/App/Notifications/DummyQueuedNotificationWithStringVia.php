<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DummyQueuedNotificationWithStringVia extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return 'mail';
    }
}
