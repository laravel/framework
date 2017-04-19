<?php

namespace Illuminate\Events;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

trait DispatchesEvents
{
    /**
     * Dispatch an event.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatchEvent($event, $payload = [], $halt = false)
    {
        return app(DispatcherContract::class)->fire($event, $payload, $halt);
    }

    /**
     * Dispatch a halt event.
     *
     * @param  mixed  $event
     * @param  array  $payload
     * @return bool
     */
    public function dispatchHaltEvent($event, $payload = [])
    {
        return $this->dispatchEvent($event, $payload, true);
    }
}
