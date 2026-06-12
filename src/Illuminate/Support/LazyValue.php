<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Container\Container;
use RuntimeException;

/**
 * @template TReturn of mixed
 *
 * @property-read TReturn $value
 */
class LazyValue
{
    /**
     * The memoized result of the callback.
     *
     * @var TReturn
     */
    protected $value;

    /**
     * Whether the callback has been evaluated.
     */
    protected bool $wasEvaluated = false;

    /**
     * Create the LazyValue instance.
     *
     * @param  \Closure(): TReturn  $callback  The function to evaluate lazily.
     */
    public function __construct(
        protected Closure $callback,
    ) {
    }

    /**
     * Execute the callback and memoize the value.
     *
     * @return TReturn
     */
    protected function evaluate()
    {
        $this->value = call_user_func($this->callback);
        $this->wasEvaluated = true;

        return $this->value;
    }

    /**
     * Get the memoized result.
     *
     * @return TReturn
     */
    public function value()
    {
        if ($this->wasEvaluated) {
            return $this->value;
        }

        return $this->evaluate();
    }

    /**
     * Get the memoized result.
     *
     * @return TReturn
     */
    public function __invoke()
    {
        return $this->value();
    }

    public function __get($key)
    {
        if ($key !== 'value') {
            throw new RuntimeException('Unable to access undefined property on '.__CLASS__.': '.$key);
        }

        return $this->value();
    }

    /**
     * Create a LazyValue instance that uses the container to inject any dependencies before executing the callback.
     *
     * @template TContainerReturn
     *
     * @param  callable(): TContainerReturn  $callback
     * @return static<TContainerReturn>
     */
    public static function resolveWithContainer(callable $callback)
    {
        return new static(static fn () => Container::getInstance()->call($callback));
    }
}
