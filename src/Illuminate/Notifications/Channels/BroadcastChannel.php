<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\DatabaseNotificationCreated;

class BroadcastChannel extends DatabaseChannel
{
    /**
     * The event dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new database channel.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiables, Notification $notification)
    {
        foreach ($notifiables as $notifiable) {
            $databaseNotification = $this->createNotification($notifiable, $notification);

            $this->events->fire(new DatabaseNotificationCreated(
                $notifiable, $notification, $databaseNotification
            ));
        }
    }
}
