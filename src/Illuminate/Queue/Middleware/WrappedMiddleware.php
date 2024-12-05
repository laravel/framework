<?php

namespace Illuminate\Queue\Middleware;

class WrappedMiddleware
{
    protected mixed $beforeMiddleware = null;

    protected mixed $afterMiddleware = null;

    protected mixed $onFail = null;

    protected bool $middlewarePassed = false;

    public function __construct(
        protected object $middleware,
    ) {}

    public function handle($job, $next): void
    {
        if (
            $this->beforeMiddleware &&
            (
                call_user_func($this->beforeMiddleware, $job) === false ||
                $job->job->isReleased() ||
                $job->job->hasFailed()
            )
        ) {
            return;
        }

        $next = function ($job) use ($next) {
            $this->middlewarePassed = true;

            if ($this->afterMiddleware) {
                call_user_func($this->afterMiddleware, $job);
            }

            $next($job);
        };

        $this->middleware->handle($job, $next);

        if (! $this->middlewarePassed && $this->onFail) {
            call_user_func($this->onFail, $job);
        }
    }

    public function before(callable $before): object
    {
        $this->beforeMiddleware = $before;

        return $this;
    }

    public function after(callable $after): object
    {
        $this->afterMiddleware = $after;

        return $this;
    }

    public function onFail(callable $callback): object
    {
        $this->onFail = $callback;

        return $this;
    }

    public function onPass(callable $callback): object
    {
        $this->after($callback);

        return $this;
    }

    public function addHook(string $hookType, callable $hook): object
    {
        return $this->$hookType($hook);
    }
}
