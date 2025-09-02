<?php

namespace Illuminate\Support\Defer;

use ArrayAccess;
use Closure;
use Countable;
use Illuminate\Support\Collection;

class DeferredCallbackCollection implements ArrayAccess, Countable
{
    /**
     * All of the deferred callbacks.
     */
    protected array $callbacks = [];

    /**
     * Get the first callback in the collection.
     *
     * @return callable
     */
    public function first()
    {
        return array_values($this->callbacks)[0];
    }

    /**
     * Invoke the deferred callbacks.
     */
    public function invoke(): void
    {
        $this->invokeWhen(fn () => true);
    }

    /**
     * Invoke the deferred callbacks if the given truth test evaluates to true.
     */
    public function invokeWhen(?Closure $when = null): void
    {
        $when ??= fn () => true;

        $this->forgetDuplicates();

        foreach ($this->callbacks as $index => $callback) {
            if ($when($callback)) {
                rescue($callback);
            }

            unset($this->callbacks[$index]);
        }
    }

    /**
     * Remove any deferred callbacks with the given name.
     */
    public function forget(string $name): void
    {
        $this->callbacks = (new Collection($this->callbacks))
            ->reject(fn ($callback) => $callback->name === $name)
            ->values()
            ->all();
    }

    /**
     * Remove any duplicate callbacks.
     *
     * @return $this
     */
    protected function forgetDuplicates(): static
    {
        $this->callbacks = (new Collection($this->callbacks))
            ->reverse()
            ->unique(fn ($c) => $c->name)
            ->reverse()
            ->values()
            ->all();

        return $this;
    }

    /**
     * Determine if the collection has a callback with the given key.
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->forgetDuplicates();

        return isset($this->callbacks[$offset]);
    }

    /**
     * Get the callback with the given key.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->forgetDuplicates();

        return $this->callbacks[$offset];
    }

    /**
     * Set the callback with the given key.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->callbacks[] = $value;
        } else {
            $this->callbacks[$offset] = $value;
        }
    }

    /**
     * Remove the callback with the given key from the collection.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->forgetDuplicates();

        unset($this->callbacks[$offset]);
    }

    /**
     * Determine how many callbacks are in the collection.
     */
    public function count(): int
    {
        $this->forgetDuplicates();

        return count($this->callbacks);
    }
}
