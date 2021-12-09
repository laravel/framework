<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Support\Hooks\HookCollection;

trait Hookable
{
    /**
     * Run hooks statically.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @param  \Closure|null  $callback
     * @return mixed
     */
    protected static function runStaticHooks($name, $arguments = [], Closure $callback = null)
    {
        return HookCollection::for(static::class)->run($name, static::class, $arguments, $callback);
    }

    /**
     * Run hooks non-statically.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @param  \Closure|null  $callback
     * @return mixed
     */
    protected function runHooks($name, $arguments = [], Closure $callback = null)
    {
        return HookCollection::for(static::class)->run($name, $this, $arguments, $callback);
    }
}
