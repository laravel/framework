<?php

namespace Illuminate\Queue\Middleware;

class WrappedMiddleware
{
    /**
     * The callback that is executed before the middleware is handled.
     *
     * @var callable|null
     */
    protected mixed $beforeMiddleware = null;

    /**
     * The callback that is executed after the middleware is handled successfully (before `$next` is called).
     *
     * @var callable|null
     */
    protected mixed $afterMiddleware = null;

    /**
     * The callback that is executed if the middleware does not call `$next` and fails.
     *
     * @var callable|null
     */
    protected mixed $onFail = null;

    /**
     * Indicates if the middleware has passed.
     */
    protected bool $middlewarePassed = false;

    /**
     * @var object $middleware The initialized middleware instance.
     */
    public function __construct(
        protected object $middleware,
    ) {
    }

    /**
     * Handles the job by wrapping the initial middleware and executing the before and after callbacks
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
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

    /**
     * Registers a callback to be executed before the middleware is handled.
     * If the callback returns false or releases or fails the job, the middleware will be aborted.
     * The callback should accept the job instance as its only argument.
     */
    public function before(callable $before): object
    {
        $this->beforeMiddleware = $before;

        return $this;
    }

    /**
     * Registers a callback to be executed after the middleware is handled, but before `$next` is called.
     * The callback should accept the job instance as its only argument.
     */
    public function after(callable $after): object
    {
        $this->afterMiddleware = $after;

        return $this;
    }

    /**
     * Registers a callback to be executed if the middleware does not call `$next` and fails.
     * The callback should accept the job instance as its only argument.
     */
    public function onFail(callable $callback): object
    {
        $this->onFail = $callback;

        return $this;
    }

    /**
     * Same as `after`, just a more semantic name.
     */
    public function onPass(callable $callback): object
    {
        $this->after($callback);

        return $this;
    }

    /**
     * Adds a hook to the middleware and returns a wrapped middleware instance.
     * The hook should accept the job instance as its only argument.
     * Supported hooks are `before`, `after`, and `onFail`.
     */
    public function addHook(string $hookType, callable $hook): object
    {
        return $this->$hookType($hook);
    }
}
