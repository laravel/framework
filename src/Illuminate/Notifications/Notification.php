<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Notification as NotificationContract;
use Illuminate\Queue\SerializesModels;

class Notification implements NotificationContract
{
    use SerializesModels, BaseNotification;
}
