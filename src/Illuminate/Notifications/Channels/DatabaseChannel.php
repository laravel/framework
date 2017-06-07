<?php

namespace Illuminate\Notifications\Channels;

use RuntimeException;
use Illuminate\Container\Container;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Notifications\Channels\Factory;
use Illuminate\Contracts\Notifications\Channels\Dispatcher;

class DatabaseChannel implements Factory, Dispatcher
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function send($notifiable, Notification $notification)
    {
        return $notifiable->routeNotificationFor('database')->create([
            'id' => $notification->id,
            'type' => get_class($notification),
            'data' => $this->getData($notifiable, $notification),
            'read_at' => null,
        ]);
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
        if (method_exists($notification, 'toDatabase')) {
            return is_array($data = $notification->toDatabase($notifiable))
                                ? $data : $data->data;
        }

        if (method_exists($notification, 'toArray')) {
            return $notification->toArray($notifiable);
        }

        throw new RuntimeException(
            'Notification is missing toDatabase / toArray method.'
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
        return in_array($driver, ['database']);
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
            ? Container::getInstance()->make(DatabaseChannel::class)
            : null;
    }
}
