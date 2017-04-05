<?php

namespace Illuminate\Contracts\Notifications\Channels;

use Illuminate\Notifications\Notification;

interface Dispatcher
{
    /**
     * Send the given notification.
     *
     * @param  $notifiable
     * @param  \Illuminate\Notifications\Notification $notification
     * @return mixed
     */
    public function send($notifiable, Notification $notification);
}