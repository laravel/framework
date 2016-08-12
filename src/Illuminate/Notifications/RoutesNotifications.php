<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Str;
use Illuminate\Contracts\Notifications\Dispatcher;

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
        app(Dispatcher::class)->send([$this], $instance);
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @param  string|\Closure|null  $attribute
     * @return mixed
     */
    public function routeNotificationFor($driver, $attribute = null)
    {
        if (method_exists($this, $method = 'routeNotificationFor'.Str::studly($driver))) {
            return $this->{$method}();
        }

        if (is_string($attribute)) {
            return $this->$attribute;
        }

        if ($attribute instanceof \Closure) {
            return $attribute($this);
        }
    }
}
