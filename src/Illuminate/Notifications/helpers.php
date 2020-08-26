<?php

use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

if (! function_exists('notify')) {
    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  Collection|array|mixed  $notifiables
     * @param  Notification  $notification
     * @return void
     */
    function notify($notifiables, Notification $notification): void
    {
        NotificationFacade::send($notifiables, $notification);
    }
}
