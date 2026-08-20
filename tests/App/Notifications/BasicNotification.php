<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class BasicNotification extends Notification
{
    public function via()
    {
        return ['test'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }
}
