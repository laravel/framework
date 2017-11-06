<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher;

trait HandlesEvents
{
    /**
     * Handles notification dispatching as event listener
     *
     * @param $event
     */
    public function handle($event)
    {
        app(Dispatcher::class)->sendNow($this->routeNotificationForEvent($event), $this);
    }

    /**
     * Route notification for a given event
     *
     * @param $event
     * @return string $notifiable
     */
    public function routeNotificationForEvent($event)
    {
        //
    }
}