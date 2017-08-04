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
        DatabaseNotification::whereIn('id', $this->pluck('id')->toArray())
            ->whereNull('read_at')
            ->update(['read_at' => new \Carbon\Carbon]);
    }
}
