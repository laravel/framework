<?php

namespace Illuminate\Notifications\Channels;

use RuntimeException;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;

class BroadcastChannel
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
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|null
     */
    public function send($notifiable, Notification $notification)
    {
        return $this->events->fire(new BroadcastNotificationCreated(
            $notifiable, $notification, $this->getData($notifiable, $notification)
        ));
    }

    /**
     * Get the data for the notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function getData($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toBroadcast')) {
            return $notification->toBroadcast($notifiable);
        }

        if (method_exists($notification, 'toArray')) {
            return $notification->toArray($notifiable);
        }

        throw new RuntimeException(
            'Notification is missing toArray method.'
        );
    }
}
