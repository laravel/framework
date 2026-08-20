<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\FailOnTimeout;

#[FailOnTimeout]
class FailOnTimeoutNotification extends Notification
{
}
