<?php

namespace Illuminate\Notifications;

use Illuminate\Database\Eloquent\Collection;

class DatabaseNotificationCollection extends Collection
{
    /**
     * Mark all notification as read.
     *
     * @return void
     */
    public function markAsRead()
    {
        $this->each(function ($notification) {
            $notification->markAsRead();
        });
    }
}
