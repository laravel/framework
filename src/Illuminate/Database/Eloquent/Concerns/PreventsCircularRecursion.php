<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Onceable;
use WeakMap;

trait PreventsCircularRecursion
{
    /**
     * The cache of objects processed to prevent infinite recursion.
     *
     * @var \WeakMap<static, array<string, mixed>>
     */
    protected static $recursionCache;

    /**
     * Prevent a method from being called multiple times on the same object within the same call stack.
     *
     * @param  callable  $callback
     * @param  mixed  $default
     * @return mixed
     */
    protected function withoutRecursion($callback, $default = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);

        $onceable = Onceable::tryFromTrace($trace, $callback);

        $object = $onceable->object ?? $this;

        $stack = static::getRecursiveCallStack($object);

        if (array_key_exists($onceable->hash, $stack)) {
            return $stack[$onceable->hash];
        }

        try {
            $stack[$onceable->hash] = is_callable($default) ? call_user_func($default) : $default;

            static::getRecursionCache()->offsetSet($object, $stack);

            return call_user_func($onceable->callable);
        } finally {
            if ($stack = Arr::except($this->getRecursiveCallStack($object), $onceable->hash)) {
                static::getRecursionCache()->offsetSet($object, $stack);
            } elseif (static::getRecursionCache()->offsetExists($object)) {
                static::getRecursionCache()->offsetUnset($object);
            }
        }
    }

    /**
     * Get the current stack of methods being called recursively.
     *
     * @param  object  $object
     * @return array
     */
    protected static function getRecursiveCallStack($object): array
    {
        return static::getRecursionCache()->offsetExists($object)
            ? static::getRecursionCache()->offsetGet($object)
            : [];
    }

    /**
     * Get the current recursion cache being used by the model.
     *
     * @return \WeakMap
     */
    protected static function getRecursionCache()
    {
        return static::$recursionCache ??= new WeakMap();
    }
}
