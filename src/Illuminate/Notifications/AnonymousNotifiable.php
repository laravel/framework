<?php

namespace Illuminate\Notifications;

use BadMethodCallException;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Fluent;
use InvalidArgumentException;

class AnonymousNotifiable extends Fluent
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
     * Add dynamic properties to the instance.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $attributes
     * @return $this
     */
    public function with($attributes)
    {
        if ($attributes instanceof Arrayable) {
            $attributes = $attributes->toArray();
        }

        $this->attributes = array_merge($this->attributes, $attributes);

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
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
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

    /**
     * Disable dynamic calls to set a parameter value.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return void
     */
    public function __call($method, $parameters)
    {
        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
