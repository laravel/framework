<?php

namespace Illuminate\Support\Traits;

trait WhenTrait
{
    /**
     * Apply the callback if the value is truthy.
     *
     * @param  bool $condition
     * @param  callable $callback
     * @param  callable|null $default
     * @return $this
     */
    public function when($condition, callable $callback, callable $default = null)
    {
        if ($condition) {
            return $callback($this, $condition);
        } elseif ($default) {
            return $default($this, $condition);
        }

        return $this;
    }
}