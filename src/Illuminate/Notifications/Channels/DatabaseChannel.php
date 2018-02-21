<?php

namespace Illuminate\Notifications\Channels;

use RuntimeException;
use Illuminate\Notifications\Notification;

class DatabaseChannel
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
        return $notifiable->routeNotificationFor('database', $notification)->create([
            'id' => $notification->id,
            'type' => $this->getType($notification),
            'data' => $this->getData($notifiable, $notification),
            'read_at' => null,
        ]);
    }

    /**
     * Get the type of the notification.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function getType(Notification $notification)
    {
        if (method_exists($notification, 'broadcastType')) {
            return $notification->broadcastType();
        }

        return get_class($notification);
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
}
