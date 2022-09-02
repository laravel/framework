<?php

namespace Illuminate\Support\Traits;

use Closure;
use Illuminate\Support\HigherOrderWhenProxy;

trait Conditionable
{
    /**
     * Apply the callback if the given "value" is (or resolves to) truthy or true with strict mode.
     *
     * @template TWhenParameter
     * @template TWhenReturnType
     *
     * @param  (\Closure($this): TWhenParameter)|TWhenParameter|null $value
     * @param  (callable($this, TWhenParameter): TWhenReturnType)|null  $callback
     * @param  (callable($this, TWhenParameter): TWhenReturnType)|null  $default
     * @param  bool|null  $strict
     * @return $this|TWhenReturnType
     */
    public function when($value = null, callable $callback = null, callable $default = null, ?bool $strict = false)
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (func_num_args() === 0) {
            return new HigherOrderWhenProxy($this);
        }

        if (func_num_args() === 1) {
            return (new HigherOrderWhenProxy($this))->condition($value);
        }

        if ($strict === true ? $value === true : $value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is (or resolves to) falsy or false with strict mode.
     *
     * @template TUnlessParameter
     * @template TUnlessReturnType
     *
     * @param  (\Closure($this): TUnlessParameter)|TUnlessParameter|null  $value
     * @param  (callable($this, TUnlessParameter): TUnlessReturnType)|null  $callback
     * @param  (callable($this, TUnlessParameter): TUnlessReturnType)|null  $default
     * @param  bool|null  $strict
     * @return $this|TUnlessReturnType
     */
    public function unless($value = null, callable $callback = null, callable $default = null, bool $strict = false)
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (func_num_args() === 0) {
            return (new HigherOrderWhenProxy($this))->negateConditionOnCapture();
        }

        if (func_num_args() === 1) {
            return (new HigherOrderWhenProxy($this))->condition(! $value);
        }

        if ($strict === true ? $value === false : ! $value) {
            return $callback($this, $value) ?? $this;
        } elseif ($default) {
            return $default($this, $value) ?? $this;
        }

        return $this;
    }
}
