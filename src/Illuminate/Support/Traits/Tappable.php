<?php

namespace Illuminate\Support\Traits;

trait Tappable
{
    /**
     * Call the given Closure with this instance then return the instance.
     *
     * @param  (callable($this): mixed)|int|null  $callback
     * @return ($callback is null ? \Illuminate\Support\HigherOrderTapProxy : ($callback is int ? \Illuminate\Support\HigherOrderTapProxy : $this))
     */
    public function tap($callback = null)
    {
        return tap($this, $callback);
    }

    /**
     * Call the given Closure with this instance until the given condition returns false
     * then return the instance.
     *
     * @param  (callable(): bool)  $until
     * @param  (\Closure($this): mixed)|null  $callback
     * @return ($callback is null ? \Illuminate\Support\HigherOrderTapProxy : $this)
     */
    public function tapUntil($until, $callback = null)
    {
        return tap_until($this, $until, $callback);
    }
}
