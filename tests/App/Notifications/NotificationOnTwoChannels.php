<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class NotificationOnTwoChannels extends Notification
{
    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }
}
