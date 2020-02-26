<?php

namespace Illuminate\Contracts\Notifications;

interface Notification
{
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn();

    /**
     * Set the locale to send this notification in.
     *
     * @param  string  $locale
     * @return $this
     */
    public function locale($locale);
}
