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
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
                            ->whereNotNull('read_at')
                            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
                            ->whereNull('read_at')
                            ->orderBy('created_at', 'desc');
    }
}
