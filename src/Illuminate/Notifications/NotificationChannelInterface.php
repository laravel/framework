<?php
namespace Illuminate\Notifications;

use Illuminate\Notifications\Notification;

interface NotificationChannelInterface
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification);
}
