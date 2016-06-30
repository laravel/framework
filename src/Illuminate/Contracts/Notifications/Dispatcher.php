<?php

namespace Illuminate\Contracts\Notifications;

interface Dispatcher
{
    /**
     * Dispatch the given notification instance to the given notifiable.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $instance
     * @param  array  $channels
     * @return void
     */
    public function dispatch($notifiable, $instance, array $channels = []);
}
