<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher;
use InvalidArgumentException;

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
     * @param  string  $channel
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function route($channel, $route)
    {
        if ($channel === 'database') {
            throw new InvalidArgumentException('The database channel does not support on-demand notifications.');
        }

        $this->routes[$channel] = $route;

        return $this;
    }

    /**
     * Send the given notification.
     *
     * @return void
     */
    public function notify($notification)
    {
        app(Dispatcher::class)->send($this, $notification);
    }

    /**
     * Send the given notification immediately.
     *
     * @return void
     */
    public function notifyNow($notification)
    {
        app(Dispatcher::class)->sendNow($this, $notification);
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     */
    public function routeNotificationFor($driver)
    {
        return $this->routes[$driver] ?? null;
    }

    /**
     * Get the value of the notifiable's primary key.
     */
    public function getKey()
    {
        //
    }
}
