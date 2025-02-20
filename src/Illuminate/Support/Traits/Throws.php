<?php

namespace Illuminate\Support\Traits;

use Closure;
use RuntimeException;

trait Throws
{
    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TThrowable of \Throwable
     *
     * @param  (\Closure($this): mixed)|mixed  $value
     * @param  TThrowable|class-string<TThrowable>|(\Closure($this):TThrowable|class-string<TThrowable>)|string  $throwable
     * @param  mixed  ...$arguments
     * @return $this
     *
     * @throws TThrowable
     */
    public function throwIf($value, $throwable, ...$arguments)
    {
        if ($value instanceof Closure ? $value($this) : $value) {
            if ($throwable instanceof Closure) {
                $throwable = $throwable($this);
            }

            if (! class_exists($throwable)) {
                [$throwable, $arguments] = [RuntimeException::class, [$throwable]];
            }

            if (is_string($throwable)) {
                $throwable = new $throwable(...$arguments);
            }

            throw $throwable;
        }

        return $this;
    }

    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TThrowable of \Throwable
     *
     * @param  (\Closure($this): mixed)|mixed  $value
     * @param  TThrowable|class-string<TThrowable>|(\Closure($this):TThrowable|class-string<TThrowable>)|string  $throwable
     * @param  mixed  ...$arguments
     * @return $this
     *
     * @throws TThrowable
     */
    public function throwUnless($value, $throwable, ...$arguments)
    {
        if (! ($value instanceof Closure ? $value($this) : $value)) {
            if ($throwable instanceof Closure) {
                $throwable = $throwable($this);
            }

            if (! class_exists($throwable)) {
                [$throwable, $arguments] = [RuntimeException::class, [$throwable]];
            }

            if (is_string($throwable)) {
                $throwable = new $throwable(...$arguments);
            }

            throw $throwable;
        }

        return $this;
    }
}
