<?php

namespace Illuminate\Concurrency;

use Illuminate\Concurrency\Defer\DeferredCallback;
use Illuminate\Concurrency\Defer\DeferredCallbackCollection;

/**
 * Defer execution of the given callback.
 *
 * @param  callable|null  $callback
 * @param  string|null  $name
 * @param  bool  $always
 * @return \Illuminate\Concurrency\Defer\DeferredCallback
 */
function defer(?callable $callback = null, ?string $name = null, bool $always = false)
{
    if ($callback === null) {
        return app(DeferredCallbackCollection::class);
    }

    return tap(
        new DeferredCallback($callback, $name, $always),
        fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
    );
}
