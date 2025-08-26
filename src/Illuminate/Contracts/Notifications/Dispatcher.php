<?php

namespace Illuminate\Contracts\Notifications;

interface Dispatcher
{
    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  \Illuminate\Support\Collection|mixed  $notifiables
     * @return void
     */
    public function send($notifiables, $notification);

    /**
     * Send the given notification immediately.
     *
     * @param  \Illuminate\Support\Collection|mixed  $notifiables
     * @return void
     */
    public function sendNow($notifiables, $notification, ?array $channels = null);
}
