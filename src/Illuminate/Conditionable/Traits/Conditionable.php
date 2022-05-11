<?php

namespace Illuminate\Support\Traits;

use Closure;
use Illuminate\Support\HigherOrderWhenProxy;

trait Conditionable
{
    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TWhenReturnType
     *
     * @param  bool  $value
     * @param  (callable($this): TWhenReturnType)|null  $callback
     * @param  (callable($this): TWhenReturnType)|null  $default
     * @return $this|TWhenReturnType
     */
    public function when($value, callable $callback = null, callable $default = null)
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (! $callback) {
            return new HigherOrderWhenProxy($this, $value);
        }

        if ($value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is (or resolves to) falsy.
     *
     * @template TUnlessReturnType
     *
     * @param  bool  $value
     * @param  (callable($this): TUnlessReturnType)  $callback
     * @param  (callable($this): TUnlessReturnType)|null  $default
     * @return $this|TUnlessReturnType
     */
    public function unless($value, callable $callback = null, callable $default = null)
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (! $callback) {
            return new HigherOrderWhenProxy($this, ! $value);
        }

        if (! $value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }
}
