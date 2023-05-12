<?php

namespace Illuminate\Notifications;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @template TKey of array-key
 * @template TModel of DatabaseNotification
 *
 * @extends Collection<TKey, TModel>
 */
class DatabaseNotificationCollection extends Collection
{
    /**
     * Mark all notifications as read.
     *
     * @return void
     */
    public function markAsRead()
    {
        $this->each->markAsRead();
    }

    /**
     * Mark all notifications as unread.
     *
     * @return void
     */
    public function markAsUnread()
    {
        $this->each->markAsUnread();
    }
}
