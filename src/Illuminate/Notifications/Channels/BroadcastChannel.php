<?php

namespace Illuminate\Notifications\Channels;

use RuntimeException;
use Illuminate\Container\Container;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Notifications\Channels\Factory;
use Illuminate\Contracts\Notifications\Channels\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;

class BroadcastChannel implements Factory, Dispatcher
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
    public function __construct(EventDispatcher $events)
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
        $message = $this->getData($notifiable, $notification);

        $event = new BroadcastNotificationCreated(
            $notifiable, $notification, is_array($message) ? $message : $message->data
        );

        if ($message instanceof BroadcastMessage) {
            $event->onConnection($message->connection)
                  ->onQueue($message->queue);
        }

        return $this->events->dispatch($event);
    }

    /**
     * Get the data for the notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return mixed
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

    /**
     * Check for the driver capacity.
     *
     * @param  string  $driver
     * @return bool
     */
    public static function canHandleNotification($driver)
    {
        return in_array($driver, ['broadcast']);
    }

    /**
     * Create a new driver instance.
     *
     * @param  $driver
     * @return \Illuminate\Contracts\Notifications\Channels\Dispatcher
     */
    public static function createDriver($driver)
    {
        return static::canHandleNotification($driver)
            ? Container::getInstance()->make(BroadcastChannel::class)
            : null;
    }
}
