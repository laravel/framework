<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class QueuedNotificationWithMessageGroupMethod extends Notification implements ShouldQueue
{
    use Queueable;

    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }

    public function messageGroup()
    {
        return 'group-1';
    }
}
