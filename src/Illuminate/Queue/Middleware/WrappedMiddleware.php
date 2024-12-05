<?php

namespace Illuminate\Queue\Middleware;

class WrappedMiddleware
{
    /**
     * The callback that is executed before the middleware is handled.
     *
     * @var callable|null
     */
    protected mixed $before = null;

    /**
     * The callback that is executed after the middleware is handled successfully (before `$next` is called).
     *
     * @var callable|null
     */
    protected mixed $after = null;

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

    protected bool $skip = false;

    /**
     * @var object The initialized middleware instance.
     */
    public function __construct(
        protected object $middleware,
    ) {
    }

    /**
     * Handles the job by wrapping the initial middleware and executing the before and after callbacks.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        if ($this->skip) {
            $next($job);

            return;
        }

        if (
            $this->before &&
            (
                call_user_func($this->before, $job) === false ||
                $job->job->isReleased() ||
                $job->job->hasFailed()
            )
        ) {
            return;
        }

        $next = function ($job) use ($next) {
            $this->middlewarePassed = true;

            if ($this->after) {
                call_user_func($this->after, $job);
            }

            $next($job);
        };

        $this->middleware->handle($job, $next);

        if (! $this->middlewarePassed && $this->onFail) {
            call_user_func($this->onFail, $job);
        }
    }

    public function skipWhen(callable|bool $condition): self
    {
        $this->skip = value($condition);

        return $this;
    }

    public function skipUnless(callable|bool $condition): self
    {
        $this->skip = ! value($condition);

        return $this;
    }

    /**
     * Adds a hook to the middleware and returns a wrapped middleware instance.
     * The hook should accept the job instance as its only argument.
     * Supported hooks are `before`, `after`, and `onFail`.
     */
    public function addHook(string $hookType, callable $hook): object
    {
        $this->$hookType = $hook;

        return $this;
    }

    /**
     * Handles dynamic method calls for the various middleware hooks that are supported.
     *
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $arguments)
    {
        $hooks = [
            'before' => 'before',
            'after' => 'after',
            'onFail' => 'onFail',
            'onPass' => 'after',
        ];

        if (array_key_exists($method, $hooks)) {
            return $this->addHook($hooks[$method], $arguments[0]);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
