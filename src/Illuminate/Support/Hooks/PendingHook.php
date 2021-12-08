<?php

namespace Illuminate\Support\Hooks;

use Closure;
use RuntimeException;

class PendingHook
{
    /**
     * The resolved hook.
     *
     * @var Hook|null
     */
    protected ?Hook $hook = null;

    /**
     * Constructor.
     *
     * @param  \Closure  $callback
     * @param  bool  $isStatic
     * @return void
     */
    public function __construct(
        public Closure $callback,
        public bool $isStatic
    ) { }

    /**
     * Resolve the pending hook into a Hook instance.
     *
     * @param  object|string|null  $instance
     * @return \Illuminate\Support\Hooks\Hook
     */
    public function resolve($instance = null)
    {
        if (is_object($instance) || $this->isStatic) {
            return $this->getHook($instance);
        }

        throw new RuntimeException('Trying to resolve a non-static hook statically.');
    }

    /**
     * Resolve or return the already resolved Hook.
     *
     * @param  object|string|null  $instance
     * @return \Illuminate\Support\Hooks\Hook
     */
    protected function getHook($instance = null): Hook
    {
        if (is_null($this->hook)) {
            $this->hook = $this->isStatic
                ? call_user_func($this->callback)
                : $this->callback->call($instance);
        }

        return $this->hook;
    }
}
