<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactoryContract;

/**
 * @method static \Ably\AblyRest ably(array $config)
 * @method static void channelRoutes(array|null $attributes = null)
 * @method static \Illuminate\Contracts\Broadcasting\Broadcaster connection(string|null $name = null)
 * @method static mixed driver(string|null $name = null)
 * @method static \Illuminate\Broadcasting\PendingBroadcast event(mixed|null $event = null)
 * @method static \Illuminate\Broadcasting\BroadcastManager extend(string $driver, \Closure $callback)
 * @method static \Illuminate\Broadcasting\BroadcastManager forgetDrivers()
 * @method static \Illuminate\Contracts\Foundation\Application getApplication()
 * @method static string getDefaultDriver()
 * @method static void purge(string|null $name = null)
 * @method static \Pusher\Pusher pusher(array $config)
 * @method static void queue(mixed $event)
 * @method static void routes(array|null $attributes = null)
 * @method static \Illuminate\Broadcasting\BroadcastManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static void setDefaultDriver(string $name)
 * @method static string|null socket(\Illuminate\Http\Request|null $request = null)
 * @method static void userRoutes(array|null $attributes = null)
 *
 * @see \Illuminate\Contracts\Broadcasting\Factory
 */
class Broadcast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BroadcastingFactoryContract::class;
    }
}
