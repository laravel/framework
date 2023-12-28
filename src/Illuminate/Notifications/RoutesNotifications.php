<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Support\Str;

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
        app(Dispatcher::class)->send($this, $instance);
    }

    /**
     * Send the notification if the given truth test passes.
     *
     * @param  bool  $boolean
     * @param  mixed  $instance
     * @return void
     */
    public function notifyIf($boolean, $instance)
    {
        if ($boolean) {
            app(Dispatcher::class)->send($this, $instance);
        }
    }

    /**
     * Send the notification unless the given truth test passes.
     *
     * @param  bool  $boolean
     * @param  mixed  $instance
     * @return void
     */
    public function notifyUnless($boolean, $instance)
    {
        if (! $boolean) {
            app(Dispatcher::class)->send($this, $instance);
        }
    }

    /**
     * Send the given notification immediately.
     *
     * @param  mixed  $instance
     * @param  array|null  $channels
     * @return void
     */
    public function notifyNow($instance, array $channels = null)
    {
        app(Dispatcher::class)->sendNow($this, $instance, $channels);
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @param  \Illuminate\Notifications\Notification|null  $notification
     * @return mixed
     */
    public function routeNotificationFor($driver, $notification = null)
    {
        if (method_exists($this, $method = 'routeNotificationFor'.Str::studly($driver))) {
            return $this->{$method}($notification);
        }

        return match ($driver) {
            'database' => $this->notifications(),
            'mail' => $this->email,
            default => null,
        };
    }
}
