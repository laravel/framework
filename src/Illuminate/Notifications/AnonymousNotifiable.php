<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher;

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
     * @param  mixed  $route
     * @return $this
     */
    public function route($channel, $route)
    {
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
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        return $this->routes[$driver] ?? null;
    }
}
