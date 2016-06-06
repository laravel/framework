<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;

trait RoutesNotifications
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $instance
     * @return void
     */
    public function notify($instance)
    {
        $manager = app(TransportManager::class);

        $notifications = Transports\Notification::notificationsFromInstance(
            $this, $instance
        );

        if ($instance instanceof ShouldQueue) {
            return $this->queueNotifications($notifications);
        }

        foreach ($notifications as $notification) {
            $manager->send($notification->application(
                config('app.name'), config('app.logo')
            ));
        }
    }

    /**
     * Queue the given notification instances.
     *
     * @param  array[\Illuminate\Notifcations\Transports\Notification]
     * @return void
     */
    protected function queueNotifications(array $notifications)
    {
        dispatch(new SendQueuedNotifications($notifications));
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        if (method_exists($this, $method = 'routeNotificationFor'.Str::studly($driver))) {
            return $this->{$method}();
        }

        switch ($driver) {
            case 'database':
                return $this->notifications();
            case 'mail':
                return $this->email;
            case 'nexmo':
                return $this->phone_number;
        }
    }
}
