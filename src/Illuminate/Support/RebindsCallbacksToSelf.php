<?php

declare(strict_types=1);

namespace Illuminate\Support;

use Closure;
use ReflectionFunction;

trait RebindsCallbacksToSelf
{
    /**
     * Binds the provided callback to the class instance.
     *
     * @throws \ReflectionException
     */
    protected function bindCallbackToSelf(Closure $callback): ?Closure
    {
        $reflector = new ReflectionFunction($callback);

        // We only want to rebind anonymous functions.
        if ($reflector->isAnonymous()) {
            if ($reflector->isStatic()) {
                // Static functions are bound without $this.
                $callback = $callback->bindTo(null, static::class);
            } else {
                // Non-static functions are bound to $this.
                $callback = $callback->bindTo($this, static::class);
            }
        }

        return $callback;
    }
}
