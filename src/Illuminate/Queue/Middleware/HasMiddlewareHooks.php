<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Support\Traits\ForwardsCalls;

trait HasMiddlewareHooks
{
    use ForwardsCalls;
    protected mixed $hookWrapper = null;

    public function addHook(string $hookType, callable $hook): WrappedMiddleware
    {
        return $this->forwardCallTo($this->getHookWrapper(), $hookType, [$hook]);
    }

    protected function getHookWrapper(): WrappedMiddleware
    {
        if (! $this->hookWrapper) {
            $this->hookWrapper = new WrappedMiddleware($this);
        }

        return $this->hookWrapper;
    }

    public function before(callable $callback): WrappedMiddleware
    {
        return $this->addHook('before', $callback);
    }

    public function after(callable $callback): WrappedMiddleware
    {
        return $this->addHook('after', $callback);
    }

    public function onFail(callable $callback): WrappedMiddleware
    {
        return $this->addHook('onFail', $callback);
    }

    public function onPass(callable $callback): WrappedMiddleware
    {
        return $this->addHook('after', $callback);
    }
}
