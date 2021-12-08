<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Support\Hooks\HookCollection;
use Illuminate\Support\Hooks\TraitHook;

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
     * Run trait hooks statically.
     *
     * @param  string  $prefix
     * @param  array  $arguments
     * @return void
     */
    protected static function runStaticTraitHooks($prefix, $arguments = [])
    {
        (new TraitHook($prefix))->run(static::class, $arguments);
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

    /**
     * Run trait hooks.
     *
     * @param  string  $prefix
     * @param  array  $arguments
     * @return void
     */
    protected function runTraitHooks($prefix, $arguments = [])
    {
        (new TraitHook($prefix))->run($this, $arguments);
    }
}
