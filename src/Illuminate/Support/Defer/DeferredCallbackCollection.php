<?php

namespace Illuminate\Support\Defer;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\EnumeratesValues;

class DeferredCallbackCollection extends Collection
{
    use EnumeratesValues;

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
     * @param  \Closure|null  $when
     * @return void
     */
    public function invokeWhen(?Closure $when = null): void
    {
        $when ??= fn () => true;

        $this->forgetDuplicates();

        foreach ($this->items as $index => $callback) {
            if ($when($callback)) {
                rescue($callback);
            }

            unset($this->callbacks[$index]);
        }
    }

    /**
     * Remove any duplicate callbacks.
     *
     * @return $this
     */
    protected function forgetDuplicates(): self
    {
        $this->callbacks = $this
                            ->reverse()
                            ->unique(fn ($c) => $c->name)
                            ->reverse()
                            ->values()
                            ->all();

        return $this;
    }

    /**
     * Determine if the collection has a callback with the given key.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->forgetDuplicates();

        return parent::offsetExists($offset);
    }

    /**
     * Get the callback with the given key.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->forgetDuplicates();

        return parent::offsetGet($offset);
    }

    /**
     * Remove the callback with the given key from the collection.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->forgetDuplicates();

        parent::offsetUnset($offset);
    }

    /**
     * Determine how many callbacks are in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        $this->forgetDuplicates();

        return parent::count();
    }
}
