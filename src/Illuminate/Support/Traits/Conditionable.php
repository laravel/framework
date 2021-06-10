<?php

namespace Illuminate\Support\Traits;

use Illuminate\Support\HigherOrderWhenProxy;

trait Conditionable
{
    /**
     * Apply the callback if the given "value" is truthy.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @param  callable|null  $default
     *
     * @return mixed
     */
    public function when($value, callable $callback = null, callable $default = null)
    {
        if (! $callback) {
            return new HigherOrderWhenProxy($this, $value);
        }

        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is falsy.
     *
     * @param  mixed  $value
     * @param  callable|null  $callback
     * @param  callable|null  $default
     *
     * @return mixed
     */
    public function unless($value, callable $callback = null, callable $default = null)
    {
        if (! $callback) {
            return new HigherOrderWhenProxy($this, ! $value);
        }

        if (! $value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }
}
