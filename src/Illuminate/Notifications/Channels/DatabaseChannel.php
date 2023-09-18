<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Notifications\Exceptions\DatabaseTypeNotificationMissingException;
use Illuminate\Notifications\Notification;
use RuntimeException;

class DatabaseChannel
{
    /**
     * Prevents database notification without a database type.
     *
     * @var bool
     */
    protected static $requireDatabaseType = false;

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function send($notifiable, Notification $notification)
    {
        return $notifiable->routeNotificationFor('database', $notification)->create(
            $this->buildPayload($notifiable, $notification)
        );
    }

    /**
     * Build an array payload for the DatabaseNotification Model.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array
     */
    protected function buildPayload($notifiable, Notification $notification)
    {
        return [
            'id' => $notification->id,
            'type' => $this->getDatabaseType($notifiable, $notification),
            'data' => $this->getData($notifiable, $notification),
            'read_at' => null,
        ];
    }

    /**
     * Return the database type.
     */
    protected function getDatabaseType(mixed $notifiable, Notification $notification): string
    {
        if (method_exists($notification, 'databaseType')) {
            return $notification->databaseType($notifiable);
        }

        if (static::requiresDatabaseType()) {
            throw new DatabaseTypeNotificationMissingException($notification);
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

        throw new RuntimeException('Notification is missing toDatabase / toArray method.');
    }

    /**
     * Prevent database notification from being used without database type.
     */
    public static function requireDatabaseType(bool $requireDatabaseType = true): void
    {
        static::$requireDatabaseType = $requireDatabaseType;
    }

    /**
     * Determine if database notification require database type.
     */
    public static function requiresDatabaseType(): bool
    {
        return static::$requireDatabaseType;
    }
}
