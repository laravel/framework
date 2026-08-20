<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Notification;

class NotificationWithAfterSendingMethod extends Notification
{
    public static $afterSendingNotifiable;
    public static $afterSendingChannel;
    public static $afterSendingResponse;

    public function via()
    {
        return ['test'];
    }

    public function afterSending($notifiable, $channel, $response)
    {
        static::$afterSendingNotifiable = $notifiable;
        static::$afterSendingChannel = $channel;
        static::$afterSendingResponse = $response;
    }
}
