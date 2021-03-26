<?php

namespace Illuminate\Events;

/**
 * A Dispatcher to spy on event registration
 *
 * Laravel does not store reference to listeners, only
 * store closures. Which made it difficult to list
 * application registered events and listeners
 *
 * @internal
 */
class SpyDispatcher extends Dispatcher
{
    public function makeListener($listener, $wildcard = false)
    {
        if (is_string($listener)) {
            return $listener;
        }

        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return $listener[0].'@'.$listener[1] ?? 'handle';
        }

        return 'Closure';
    }

    public function events(): array
    {
        return array_merge_recursive($this->listeners, $this->wildcards);
    }
}
