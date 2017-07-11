<?php

namespace Illuminate\Notifications;

use Illuminate\Contracts\Notifications\Dispatcher;

class InstantNotifiable
{
    /**
     * The array of routes a notification should be sent to.
     *
     * @var array
     */
    public $routes;

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
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        return $this->routes[$driver] ?? null;
    }

    /**
     * Register the given route.
     *
     * @param  mixed  $route
     * @param  string  $channel
     * @return $this
     */
    public function addRoute($route, $channel)
    {
        $this->routes[$channel] = $route;

        return $this;
    }
}
