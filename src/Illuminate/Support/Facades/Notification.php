<?php

namespace Illuminate\Support\Facades;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Testing\Fakes\NotificationFake;

/**
 * @method static void send(\Illuminate\Support\Collection | array | mixed $notifiables, mixed $notification) Send the given notification to the given notifiable entities.
 * @method static void sendNow(\Illuminate\Support\Collection | array | mixed $notifiables, mixed $notification, array | null $channels) Send the given notification immediately.
 * @method static mixed channel(string | null $name) Get a channel instance.
 * @method static string getDefaultDriver() Get the default channel driver name.
 * @method static string deliversVia() Get the default channel driver name.
 * @method static void deliverVia(string $channel) Set the default channel driver name.
 * @method static mixed driver(string $driver) Get a driver instance.
 * @method static $this extend(string $driver, \Closure $callback) Register a custom driver creator Closure.
 * @method static array getDrivers() Get all of the created "drivers".
 *
 * @see \Illuminate\Notifications\ChannelManager
 */
class Notification extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return void
     */
    public static function fake()
    {
        static::swap(new NotificationFake);
    }

    /**
     * Begin sending a notification to an anonymous notifiable.
     *
     * @param  string  $channel
     * @param  mixed  $route
     * @return \Illuminate\Notifications\AnonymousNotifiable
     */
    public static function route($channel, $route)
    {
        return (new AnonymousNotifiable)->route($channel, $route);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ChannelManager::class;
    }
}
