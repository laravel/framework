<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher;
use InvalidArgumentException;

use function Illuminate\Support\enum_value;

class AnonymousNotifiable
{
    /**
     * All of the notification routing information.
     *
     * @var array
     */
    public $routes = [];

    /**
     * Add routing information to the target.
     *
     * @param  \UnitEnum|string  $channel
     * @param  mixed  $route
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function route($channel, $route)
    {
        $channel = enum_value($channel);

        if ($channel === 'database') {
            throw new InvalidArgumentException('The database channel does not support on-demand notifications.');
        }

        $this->routes[$channel] = $route;

        return $this;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notification
     * @return void
     */
    public function notify($notification)
    {
        app(Dispatcher::class)->send($this, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @param  mixed  $notification
     * @return void
     */
    public function notifyNow($notification)
    {
        app(Dispatcher::class)->sendNow($this, $notification);
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param  \UnitEnum|string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        $driver = enum_value($driver);

        return $this->routes[$driver] ?? null;
    }

    /**
     * Get the value of the notifiable's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        //
    }
}
