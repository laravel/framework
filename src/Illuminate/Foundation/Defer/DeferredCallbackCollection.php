<?php

namespace Illuminate\Foundation\Defer;

use ArrayAccess;
use Closure;
use Illuminate\Support\Collection;

class DeferredCallbackCollection implements ArrayAccess
{
    /**
     * All of the deferred callbacks.
     *
     * @var array
     */
    protected array $callbacks = [];

    /**
     * Invoke the deferred callbacks.
     *
     * @return void
     */
    public function invoke(): void
    {
        $this->invokeWhen(fn () => true);
    }

    /**
     * Invoke the deferred callbacks if the given truth test evaluates to true.
     *
     * @param  \Closure  $when
     * @return void
     */
    public function invokeWhen(?Closure $when = null): void
    {
        $when ??= fn () => true;

        foreach ($this->callbacks as $index => $callback) {
            if ($when($callback)) {
                rescue($callback);
            }

            unset($this->callbacks[$index]);
        }
    }

    /**
     * Determine if the collection has a callback with the given key.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->callbacks[$offset]);
    }

    /**
     * Get the callback with the given key.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->callbacks[$offset];
    }

    /**
     * Set teh callback with the given key.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->callbacks[$offset] = $value;
    }

    /**
     * Remove the callback with the given key from the collection.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->callbacks[$offset]);
    }
}
