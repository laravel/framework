<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactoryContract;

/**

 *
 * @mixin \Illuminate\Broadcasting\BroadcastManager
 * @mixin \Illuminate\Broadcasting\Broadcasters\Broadcaster
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
