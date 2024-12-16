<?php

namespace Illuminate\Support\Defer;

use ArrayAccess;
use Closure;
use Countable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Collection;

/**
 * @method bool offsetExists(mixed $offset) Determine if the collection has a callback with the given key.
 * @method mixed offsetGet(mixed $offset) Get the callback with the given key.
 * @method void offsetSet(mixed $offset, mixed $value) Set the callback with the given key.
 * @method void offsetUnset(mixed $offset) Remove the callback with the given key from the collection.
 * @method void forget(string $name) Remove any deferred callbacks with the given name.
 * @method int count() Determine how many callbacks are in the collection.
 */
class DeferredCallbackCollection implements ArrayAccess, Countable
{
    use ForwardsCalls;

    private static $forgetDuplicateMethods = [
        'offsetExists', 'offsetGet', 'offsetUnset',
        'count', 'forget',
    ];

    /**
     * All of the deferred callbacks.
     *
     * @var array
     */
    protected Collection $callbacks;

    public function __construct()
    {
        $this->callbacks = new Collection;
    }

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

        foreach ($this->callbacks as $index => $callback) {
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
        $this->callbacks = $this->callbacks
            ->reverse()
            ->unique(fn ($c) => $c->name)
            ->reverse();

        return $this;
    }

    public function __call($method, $parameters)
    {
        if (in_array($method, ['first', 'offsetSet', ...self::$forgetDuplicateMethods])) {
            if (in_array($method, self::$forgetDuplicateMethods)) {
                $this->forgetDuplicates();
            }

            return $this->forwardCallTo($this->callbacks, $method, $parameters);
        }
    }
}
