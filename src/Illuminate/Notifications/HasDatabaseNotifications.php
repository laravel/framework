<?php

namespace Illuminate\Notifications;

trait HasDatabaseNotifications
{
    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->setConnection($this->getNotifiableConnectionName())
                    ->morphMany(DatabaseNotification::class, 'notifiable')
                    ->latest();
    }

    /**
     * Get the entity's read notifications.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function readNotifications()
    {
        return $this->notifications()->read();
    }

    /**
     * Get the entity's unread notifications.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }

    /**
     * Get updatable notifiable connection property
     *
     * @return string
     */
    public function getNotifiableConnectionName()
    {
        return $this->notifiableConnection ?: $this->connection;
    }
}
