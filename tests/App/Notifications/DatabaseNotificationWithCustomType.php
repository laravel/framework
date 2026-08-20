<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class DatabaseNotificationWithCustomType extends Notification
{
    public function toDatabase($notifiable)
    {
        return new DatabaseMessage(['invoice_id' => 1]);
    }

    public function databaseType()
    {
        return 'MONTHLY';
    }

    public function initialDatabaseReadAtValue()
    {
        return Carbon::now();
    }
}
