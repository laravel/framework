<?php

namespace Illuminate\Events;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * @property Container $container
 */
trait EventHooks
{
    /**
     * The "before" event hook key.
     */
    protected const HOOK_BEFORE = 'before';

    /**
     * The "after" event hook key.
     */
    protected const HOOK_AFTER = 'after';

    /**
     * The "failure" event hook key.
     */
    protected const HOOK_FAILURE = 'failure';

    /**
     * The available event hooks.
     */
    protected const HOOKS = [self::HOOK_BEFORE, self::HOOK_AFTER, self::HOOK_FAILURE];

    /**
     * The wildcard event key.
     */
    protected const WILDCARD = '*';

    /**
     * The registered event callbacks.
     *
     * @var array<string, array<string, array<callable|string>>>
     * */
    protected array $callbacks = [];

    /**
     * Cache for results of expensive method operations.
     *
     * @var array{
     *      has_callbacks?: array<string, bool>,
     *      aggregated_callbacks?: array<string, array<callable|string>>,
     *      has_hierarchical?: array<string, bool>,
     *      hierarchical_callbacks?: array<string, array<callable|string>>,
     *      event_hierarchies?: array<string, array<string>>,
     *      event_callbacks?: array<string, array<string, array<callable|string>>>,
     *      hook_and_event_callbacks?: array<string, array<callable|string>>,
     *  }
     */
    protected array $cache = [];

    /**
     * Get registered event callbacks, optionally filtered by a hook and/or event.
     *
     * @return array<string, array<string, array<callable|string>>>
     *
     * @throws InvalidArgumentException
     */
    public function callbacks(?string $hook = null, ?string $event = null): array
    {
        return match (true) {
            is_null($hook) && is_null($event) => $this->callbacks,
            (! is_null($event)) => $this->callbacksForHookAndEvent($hook, $event),
            $hook === static::WILDCARD, (is_null($event) && ! $this->isHook($hook)) => $this->callbacksForEvent($hook),
            (is_null($event) && $this->isHook($hook)) => $this->callbacksForHook($hook)
        };
    }

    /**
     * Register callback(s) to be executed before event dispatch.
     *
     * @throws InvalidArgumentException
     */
    public function before(callable|string|array $events, callable|string|array|null $callbacks = null): static
    {
        return tap($this, function () use ($events, $callbacks) {
            $this->registerCallbacks(
                static::HOOK_BEFORE,
                $callbacks ?? $events, (is_null($callbacks) || $events === static::WILDCARD) ? null : $events
            );
        });
    }

    /**
     * Register callback(s) to be executed after event dispatch.
     *
     * @throws InvalidArgumentException
     */
    public function after(callable|string|array $events, callable|string|array|null $callbacks = null): static
    {
        return tap($this, function () use ($events, $callbacks) {
            $this->registerCallbacks(
                static::HOOK_AFTER,
                $callbacks ?? $events, (is_null($callbacks) || $events === static::WILDCARD) ? null : $events
            );
        });
    }

    /**
     * Register callback(s) to be executed if event dispatch fails.
     *
     * @throws InvalidArgumentException
     */
    public function failure(callable|string|array $events, callable|string|array|null $callbacks = null): static
    {
        return tap($this, function () use ($events, $callbacks) {
            $this->registerCallbacks(
                static::HOOK_FAILURE,
                $callbacks ?? $events, (is_null($callbacks) || $events === static::WILDCARD) ? null : $events
            );
        });
    }

    /**
     * Determine if the given string is a valid hook.
     */
    public function isHook(?string $hook = null): bool
    {
        return in_array($hook, static::HOOKS, true);
    }

    /**
     * Get callbacks for a specific hook and event.
     *
     * @return array<string, array<callable|string>>
     **/
    protected function callbacksForHookAndEvent(string $hook, string $event): array
    {
        return $this->cache['hook_and_event_callbacks'][$this->key($hook, $event)] ??=
            array_merge(
                [static::WILDCARD => $this->callbacks[$hook][static::WILDCARD] ?? []],
                array_reduce(
                    $this->eventHierarchy($event),
                    fn ($carry, $key): array => isset($this->callbacks[$hook][$key])
                        ? array_merge($carry, [$key => $this->callbacks[$hook][$key] ?? []])
                        : $carry,
                    []
                )
            );
    }

    /**
     * Get callbacks for a specific hook.
     *
     * @return array<string, array<callable|string>>
     **/
    protected function callbacksForHook(string $hook): array
    {
        return $this->callbacks[$hook] ?? [];
    }

    /**
     * Get callbacks for a specific event.
     *
     * @return array<string, array<callable|string>>
     **/
    protected function callbacksForEvent(string $event): array
    {
        return $this->cache['event_callbacks'][$event] ??= [
            static::HOOK_BEFORE => $this->callbacksForHookAndEvent(static::HOOK_BEFORE, $event),
            static::HOOK_AFTER => $this->callbacksForHookAndEvent(static::HOOK_AFTER, $event),
            static::HOOK_FAILURE => $this->callbacksForHookAndEvent(static::HOOK_FAILURE, $event),
        ];
    }

    /**
     * Get the class hierarchy for a specific event.
     *
     * @return array<string>
     */
    protected function eventHierarchy(string $event): array
    {
        return $this->cache['event_hierarchies'][$event] ??=
            array_filter(
                array_merge(
                    [$event],
                    $exists = class_exists($event) ? class_parents($event) : [],
                    $exists ? class_implements($event) : []
                )
            );
    }

    /**
     * Register event hook callbacks with the dispatcher.
     *
     * @throws InvalidArgumentException
     */
    protected function registerCallbacks(string $hook, callable|string|array $callbacks, string|array|null $events = null): void
    {
        $this->validateHook($hook);

        is_null($events)
            ? $this->registerGlobalCallbacks($hook, $callbacks)
            : $this->registerEventCallbacks($hook, $callbacks, $events);
    }

    /**
     * Register global event hook callbacks with the dispatcher.
     *
     * @throws InvalidArgumentException
     */
    protected function registerGlobalCallbacks(string $hook, callable|string|array $callbacks): void
    {
        $this->validateHook($hook);

        foreach (Arr::wrap($callbacks) as $callback) {
            $this->registerCallback($hook, static::WILDCARD, $callback);
        }
    }

    /**
     * Register event-specific event hook callbacks with the dispatcher.
     *
     * @param  callable|string|array<callable|string>  $callbacks
     * @param  string|array<string>  $events
     *
     * @throws InvalidArgumentException
     */
    protected function registerEventCallbacks(string $hook, callable|string|array $callbacks, string|array $events): void
    {
        foreach (Arr::wrap($events) as $event) {
            if (! is_string($event)) {
                throw new InvalidArgumentException('Event name must be a string, given: '.gettype($event));
            }

            foreach (Arr::wrap($callbacks) as $callback) {
                $this->registerCallback($hook, $event, $callback);
            }
        }
    }

    /**
     * Register an event hook callback.
     *
     * @throws InvalidArgumentException
     */
    protected function registerCallback(string $hook, string $event, callable|string $callback): static
    {
        $this->validateHook($hook);

        is_callable($callback) ?: $this->validateCallback($callback);

        $this->addCallbackToRegistry($hook, $event, $callback);

        $this->updateHasCallbacksCache($hook, $event);

        return $this;
    }

    /**
     * Add a callback to the registry for a specific hook and event.
     *
     *
     * @throws InvalidArgumentException
     */
    protected function addCallbackToRegistry(string $hook, string $event, callable|string $callback): void
    {
        $this->callbacks[$hook][$event][] = $callback;
    }

    /**
     * Invoke the registered event callbacks for a specific hook.
     *
     * @param  array<int, mixed>  $payload
     *
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    protected function invokeCallbacks(string $hook, string $event, array $payload): void
    {
        $this->validateHook($hook);

        foreach ($this->prepareCallbacks($hook, $event, $payload) as $callback) {
            $this->invokeCallback($callback, $payload);
        }
    }

    /**
     * Determine if the given event/hook has callbacks (including hierarchical callbacks).
     */
    protected function hasCallbacks(string $hook, string $event, array $payload): bool
    {
        $this->validateHook($hook);

        if (
            (! empty($this->callbacks) && $this->checkCacheForCallbacks($hook, $event))
                || (is_object($object = Arr::first($payload)) && $this->checkObjectForCallbacks($hook, $object))
        ) {
            return true;
        }

        return $this->cache['has_callbacks'][$this->key($hook, $event)] =
            ((! empty($this->callbacks[$hook][static::WILDCARD]))
                || (! empty($this->callbacks[$hook][$event]))
                || $this->hasHierarchicalCallbacks($hook, $event, $payload));
    }

    /**
     * Determine if the cache has registered callbacks for the given event/hook (including hierarchical callbacks).
     */
    protected function checkCacheForCallbacks(string $hook, string $event): bool
    {
        $this->validateHook($hook);

        return array_key_exists($this->key($hook, $event), $this->cache['has_callbacks'] ?? [])
            || array_key_exists($this->key($hook, static::WILDCARD), $this->cache['has_callbacks'] ?? []);
    }

    /**
     * Determine if the given event object has implemented the specified hook.
     */
    protected function checkObjectForCallbacks(string $hook, object $event): bool
    {
        $this->validateHook($hook);

        return method_exists($event, $hook);
    }

    /**
     * Determine if the given event/hook has callbacks registered for its parent(s)/interface(s).
     *
     * @param  array<int, mixed>  $payload
     */
    protected function hasHierarchicalCallbacks(string $hook, string $event, array $payload): bool
    {
        $this->validateHook($hook);

        if (! class_exists($event) && ! is_object(Arr::first($payload))) {
            return false;
        }

        if ($this->checkCacheForHierarchicalCallbacks($hook, $event, $payload)) {
            return true;
        }

        foreach (array_merge([$event], $this->eventHierarchy(get_class(Arr::first($payload)))) as $key) {
            if (! empty($this->callbacks[$hook][$key])) {
                return $this->cache['has_hierarchical'][$this->key($hook, $key)] = true;
            }
        }

        return false;
    }

    /**
     * Determine if the cache has registered hierarchical callbacks for the given event/hook.
     *
     * @param  array<int, mixed>  $payload
     */
    protected function checkCacheForHierarchicalCallbacks(string $hook, string $event, array $payload): bool
    {
        $this->validateHook($hook);

        return array_key_exists($this->key($hook, $event), $this->cache['has_hierarchical'] ?? [])
            || array_key_exists($this->key($hook, get_class(Arr::first($payload))), $this->cache['has_hierarchical'] ?? []);
    }

    /**
     * Update the cached results for the given hook and event.
     *
     * @throws InvalidArgumentException
     */
    protected function updateHasCallbacksCache(string $hook, string $event): void
    {
        $this->validateHook($hook);

        $this->cache['has_callbacks'][$this->key($hook, $event)] = true;
    }

    /**
     * Aggregates, formats, and orders callbacks for a specific hook and event.
     *
     * @param  array<int, mixed>  $payload
     * @return array<callable|string>
     *
     * @throws InvalidArgumentException
     */
    protected function prepareCallbacks(string $hook, string $event, array $payload): array
    {
        $this->validateHook($hook);

        return $this->orderCallbacks($hook, $this->aggregateCallbacks($hook, $event, $payload));
    }

    /**
     * Aggregate callbacks for a specific hook and event, including wildcard callbacks.
     *
     * @param  array<int, mixed>  $payload
     * @return array<callable|string>
     *
     * @throws InvalidArgumentException
     */
    protected function aggregateCallbacks(string $hook, string $event, array $payload): array
    {
        $this->validateHook($hook);

        return $this->cache['aggregated_callbacks'][$key = $this->key($hook, $event)] ??=
            array_merge(
                $this->callbacks[$hook][static::WILDCARD] ?? [],
                $this->aggregateHierarchicalCallbacks($hook, $event, $payload),
                (! empty($callback = $this->prepareEventObjectCallback($hook, $payload))) ? [$callback] : [],
            );
    }

    /**
     * Aggregate hierarchical callbacks for a specific hook and event.
     *
     * @param  array<int, mixed>  $payload
     * @return array<callable|string>
     *
     * @throws InvalidArgumentException
     */
    protected function aggregateHierarchicalCallbacks(string $hook, string $event, array $payload): array
    {
        $this->validateHook($hook);

        return $this->cache['hierarchical_callbacks'][$key = $this->key($hook, $event)] ??=
            array_reduce(
                array_merge([$event], (! is_object(Arr::first($payload)) ? [] : $this->eventHierarchy(get_class(Arr::first($payload))))),
                fn ($carry, $key): array => isset($this->callbacks[$hook][$key])
                    ? array_merge($carry, $this->callbacks[$hook][$key])
                    : $carry,
                []
            );
    }

    /**
     * Prepare the event object callback.
     *
     * @param  array<int, mixed>  $payload
     * @return array{0: object, 1: string}|array{}
     *
     * @throws InvalidArgumentException
     */
    protected function prepareEventObjectCallback(string $hook, array $payload): array
    {
        $this->validateHook($hook);

        return (! empty($payload) && is_object($event = Arr::first($payload)) && method_exists($event, $hook)) ? [$event, $hook] : [];
    }

    /**
     * Order the callbacks based on the hook type.
     *
     * @return array<callable|string>
     *
     * @throws InvalidArgumentException
     */
    protected function orderCallbacks(string $hook, array $callbacks): array
    {
        $this->validateHook($hook);

        // FIFO for setup hooks, LIFO for cleanup hooks
        return in_array($hook, [static::HOOK_AFTER, static::HOOK_FAILURE], true)
            ? array_reverse($callbacks)
            : $callbacks;
    }

    /**
     * Invoke the given callback with the provided payload.
     *
     * @param  array<int, mixed>  $payload
     *
     * @throws BindingResolutionException
     */
    protected function invokeCallback(callable|string $callback, array $payload): void
    {
        is_callable($callback) ?: $this->validateCallback($callback);

        match (true) {
            // the order of the following match cases is integral as we want to make callables that
            // are neither closure nor functions via the container to auto-inject their dependencies
            is_array($callback) => $this->invokeArrayCallback($callback, $payload),
            is_string($callback) => $this->invokeStringCallback($callback, $payload),
            is_callable($callback) => $callback(...$payload),
            default => $this->invalidCallback(),
        };
    }

    /**
     * Invoke the given array callback with the provided payload.
     *
     * @param  array{0: object|string, 1: string}  $callback
     * @param  array<int, mixed>  $payload
     *
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    protected function invokeArrayCallback(array $callback, array $payload): void
    {
        match (true) {

            // [$object,'method'] -- call the method
            is_object($object = Arr::first($callback))
                && is_string($method = Arr::last($callback))
                && method_exists($object, $method) => $object->{$method}(...$payload),

            // ['class', 'method'] -- make the class, call the specified method
            is_string($class = Arr::first($callback))
                && is_string($method = Arr::last($callback))
                && class_exists($class)
                && method_exists($class, $method) => $this->container()->make($class)->{$method}(...$payload),

            default => $this->invalidCallback(),
        };
    }

    /**
     * Invoke the given string callback with the provided payload.
     *
     * @param  array<int, mixed>  $payload
     *
     * @throws InvalidArgumentException
     * @throws BindingResolutionException
     */
    protected function invokeStringCallback(string $callback, array $payload): void
    {
        match (true) {

            // classname w/ handle() method -- make the class, call the handle method
            class_exists($callback)
                && method_exists($callback, 'handle') => $this->container()->make($callback)->handle(...$payload),

            // classname, invokable -- make and invoke the class
            class_exists($callback)
                && method_exists($callback, '__invoke') => $this->container()->make($callback)(...$payload),

            // class@method or class::method-- make the class, call the specified method
            ($callback = Str::of($callback)->replace('::', '@'))->contains('@')
                && class_exists($class = ($parts = $callback->explode('@'))->first())
                && method_exists($class, $method = $parts->last()) => $this->container()->make($class)->{$method}(...$payload),

            default => $this->invalidCallback(),
        };
    }

    /**
     * Generate a cache key for the given hook and event.
     */
    protected function key(string $hook, string $event): string
    {
        return "$hook:$event";
    }

    /**
     * Validate the given hook name.
     *
     * @throws InvalidArgumentException
     */
    protected function validateHook(string $hook): void
    {
        if (! $this->isHook($hook)) {
            throw new InvalidArgumentException("Invalid hook: {$hook}");
        }
    }

    /**
     * Validate the given string/array callback.
     *
     * @throws InvalidArgumentException
     */
    protected function validateCallback(array|string $callback): void
    {
        is_array($callback)
            ? $this->validateArrayCallback($callback)
            : $this->validateStringCallback($callback);
    }

    /**
     * Validate the given array callback.
     *
     * @throws InvalidArgumentException
     */
    protected function validateArrayCallback(array $callback): void
    {
        if (count($callback) !== 2
            || (! is_string($callback[1]))
            || (! is_object($callback[0]) && ! is_string($callback[0]))
            || (is_object($callback[0]) && ! method_exists($callback[0], $callback[1]))
            || (is_string($callback[0]) && (! class_exists($callback[0]) || ! method_exists($callback[0], $callback[1])))
        ) {
            $this->invalidCallback();
        }
    }

    /**
     * Validate the given string callback.
     *
     * @throws InvalidArgumentException
     */
    protected function validateStringCallback(string $callback): void
    {
        if (($count = ($parts = Str::of($callback)->replace('::', '@')->explode('@'))->count()) > 2
            || ($count === 1 && (! class_exists($class = $parts->first()) || (! method_exists($class, '__invoke') && ! method_exists($class, 'handle'))))
            || ($count === 2 && (! class_exists($class = $parts->first()) || ! method_exists($class, $parts->last())))
        ) {
            $this->invalidCallback();
        }
    }

    /**
     * Get the container instance.
     *
     * @throws RuntimeException
     **/
    protected function container(): Container
    {
        if (! isset($this->container)) {
            throw new RuntimeException(
                'Container instance is not set. Ensure the trait is used in a class that properly initializes the container.'
            );
        }

        return $this->container;
    }

    /**
     * Throw an exception for an invalid callback.
     *
     * @throws InvalidArgumentException
     */
    protected function invalidCallback(): void
    {
        throw new InvalidArgumentException('Invalid callback provided.');
    }
}
