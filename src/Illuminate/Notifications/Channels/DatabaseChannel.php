<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Events\DatabaseNotificationCreated;

class DatabaseChannel
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

            if ($notification instanceof ShouldBroadcast) {
                $this->events->fire(new DatabaseNotificationCreated(
                    $notifiable, $notification, $databaseNotification
                ));
            }
        }
    }

    /**
     * Create a database notification record for the notifiable.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Notifications\DatabaseNotification
     */
    protected function createNotification($notifiable, Notification $notification)
    {
        $message = $notification->toDatabase($notifiable);

        return $notifiable->routeNotificationFor('database')->create([
            'id' => $message->id,
            'type' => get_class($notification),
            'data' => $message->data,
            'read' => false,
        ]);
    }
}
