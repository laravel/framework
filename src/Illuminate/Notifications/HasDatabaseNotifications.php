<?php

namespace Illuminate\Notifications;

trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
                            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's read notifications.
     */
    public function readNotifications()
    {
        return $this->notifications()
                            ->whereNotNull('read_at');
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()
                            ->whereNull('read_at');
    }
}
