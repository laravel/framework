<?php

namespace Illuminate\Support;

use Illuminate\Support\Exceptions\RecursableNotFoundException;
use WeakMap;

class Recurser
{
    /**
     * The current globally used instance.
     *
     * @var static|null
     */
    protected static ?self $instance = null;

    /**
     * An empty object to use as the object for non-object-based uses.
     *
     * @var object
     */
    public readonly object $globalContext;

    /**
     * Create a new once instance.
     *
     * @param  \WeakMap<object, array<string, mixed>>  $cache
     * @return void
     */
    public function __construct(protected WeakMap $cache)
    {
        $this->globalContext = (object) [];
    }

    /**
     * Get or create the current globally used instance.
     *
     * @return static
     */
    public static function instance(): static
    {
        return static::$instance ??= new static(new WeakMap);
    }

    /**
     * Flush the recursion cache.
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$instance = null;
    }

    /**
     * Prevent a method from being called multiple times on the same instance of an object within the same call stack.
     *
     * @param  Recursable  $target
     * @return mixed
     */
    public function withoutRecursion(Recursable $target): mixed
    {
        $target->for($this->globalContext);

        if ($this->hasValue($target)) {
            return $this->getRecursedValue($target);
        }

        try {
            $this->setRecursedValue($target);

            return call_user_func($target->callback);
        } finally {
            $this->release($target);
        }
    }

    /**
     * Get the stack of methods being called recursively for the given object.
     *
     * @param  object  $instance
     * @return array
     */
    protected function getStack(object $instance): array
    {
        return $this->cache->offsetExists($instance) ? $this->cache->offsetGet($instance) : [];
    }

    /**
     * Set the stack of methods being called recursively for the given object.
     *
     * @param  object  $instance
     * @param  array  $stack
     */
    protected function setStack(object $instance, array $stack): void
    {
        if ($stack) {
            $this->cache->offsetSet($instance, $stack);
        } elseif ($this->cache->offsetExists($instance)) {
            $this->cache->offsetUnset($instance);
        }
    }

    /**
     * Check if there is a stored value for the recursable target.
     *
     * @param  Recursable  $target
     * @return bool
     */
    protected function hasValue(Recursable $target): bool
    {
        return array_key_exists($target->hash, $this->getStack($target->object));
    }

    /**
     * Get the currently stored value of the given recursable.
     *
     * @param  Recursable  $target
     * @return mixed
     */
    protected function getRecursedValue(Recursable $target): mixed
    {
        if ($this->hasValue($target)) {
            return with(
                $this->getStack($target->object)[$target->hash],
                function ($value) use ($target) {
                    if (is_callable($value)) {
                        $target->return(call_user_func($value));

                        return $this->setRecursedValue($target);
                    }

                    return $value;
                }
            );
        }

        throw RecursableNotFoundException::make($target);
    }

    /**
     * Set the currently stored value of the given recursable.
     *
     * @param  Recursable  $target
     * @return mixed
     */
    protected function setRecursedValue(Recursable $target): mixed
    {
        $stack = tap(
            $this->getStack($target->object),
            fn (array &$stack) => $stack[$target->hash] = $target->onRecursion,
        );

        $this->setStack($target->object, $stack);

        return $stack[$target->hash];
    }

    /**
     * Release a recursable from the stack.
     *
     * @param  Recursable  $target
     * @return void
     */
    protected function release(Recursable $target): void
    {
        $this->setStack($target->object, Arr::except($this->getStack($target->object), $target->hash));
    }
}
