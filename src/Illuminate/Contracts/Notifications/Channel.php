<?php

namespace Illuminate\Contracts\Notifications;

use Illuminate\Notifications\Notification;

interface Channel
{
    /**
     * Send the given notification.
     *
     * @param  mixed $notifiable
     * @param  \Illuminate\Notifications\Notification $notification
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($notifiable, Notification $notification);
}
