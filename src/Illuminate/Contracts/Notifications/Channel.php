<?php

namespace Illuminate\Contracts\Notifications;

use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;

interface Channel
{
    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send(Collection $notifiables, Notification $notification);
}
