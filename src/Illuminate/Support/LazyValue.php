<?php

namespace Illuminate\Support;

use Closure;

/**
 * @template TReturn of mixed
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
     * The function to evaluate lazily.
     *
     * @var (Closure(): TReturn)|null
     */
    protected ?Closure $callback;

    /**
     * Create the LazyValue instance.
     *
     * @param  \Closure(): TReturn  $callback
     */
    public function __construct(
        Closure $callback,
    ) {
        $this->callback = $callback;
    }

    /**
     * Execute the callback and memoize the value.
     *
     * @return TReturn
     *
     * @throws \Throwable
     */
    protected function evaluate()
    {
        $this->value = call_user_func($this->callback);
        $this->wasEvaluated = true;

        // Clear the callback to avoid retaining the full closure-scope after evaluation.
        $this->callback = null;

        return $this->value;
    }

    /**
     * Get the memoized result.
     *
     * @return TReturn
     *
     * @throws \Throwable
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
     *
     * @throws \Throwable
     */
    public function __invoke()
    {
        return $this->value();
    }
}
