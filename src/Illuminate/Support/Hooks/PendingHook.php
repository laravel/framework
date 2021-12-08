<?php

namespace Illuminate\Support\Hooks;

use Closure;
use RuntimeException;

class PendingHook
{
    protected ?Hook $hook = null;

    public function __construct(
        public Closure $callback,
        public bool $isStatic
    ) { }

    public function resolve($instance = null)
    {
        if (! $this->isStatic && is_null($instance)) {
            throw new RuntimeException('Trying to resolve a non-static hook statically.');
        }

        return $this->getHook($instance);
    }

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
