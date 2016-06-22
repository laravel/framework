<?php

namespace Illuminate\Contracts\Notifications;

interface Factory
{
    /**
     * Create a new notification for the given notifiable entities.
     *
     * @param  array  $notifiables
     * @return \Illuminate\Notifications\Notification
     */
    public function to($notifiables);
}
