<?php

namespace Illuminate\Queue\Middleware;

use Closure;
use Illuminate\Support\Collection;
use LogicException;
use Throwable;

class FailOnException
{
    /**
     * The truth-test callback to determine if the job should fail.
     *
     * @var \Closure(\Throwable, mixed): bool
     */
    protected Closure $callback;

    /**
     * Create a middleware instance.
     *
     * @param  (\Closure(\Throwable, mixed): bool)|array<array-key, class-string<\Throwable>|(\Closure(\Throwable, mixed): bool)>  $callback
     *
     * @throws \LogicException
     */
    public function __construct($callback)
    {
        if (is_array($callback)) {
            $this->ensureValidCallback($callback);
            $callback = $this->failForExceptions($callback);
        }

        $this->callback = $callback;
    }

    /**
     * Ensure that the provided array only contains class strings or callables.
     *
     * @param  array<array-key, class-string<\Throwable>|(\Closure(\Throwable, mixed): bool)>  $exceptions
     * @return void
     * @throws \LogicException
     */
    protected function ensureValidCallback(array $exceptions)
    {
        (new Collection($exceptions))->each(function ($exception) {
            $isExceptionString = is_a($exception, Throwable::class, true);
            $isCallable = is_callable($exception);

            if (! $isExceptionString && ! $isCallable) {
                throw new LogicException('Provided callback should be an array containing only Throwable class strings or callables.');
            }
        });
    }

    /**
     * Indicate that the job should fail if it encounters the given exceptions.
     *
     * @param  array<array-key, class-string<\Throwable>|(\Closure(\Throwable, mixed): bool)>  $exceptions
     * @return \Closure(\Throwable, mixed): bool
     */
    protected function failForExceptions(array $exceptions)
    {
        return static function (Throwable $throwable, $job) use ($exceptions) {
            foreach ($exceptions as $exception) {
                if (is_a($exception, Throwable::class, true)) {
                    if ($throwable instanceof $exception) {
                        return true;
                    }
                }

                return (bool) call_user_func($exception, $throwable, $job);
            }

            return false;
        };
    }

    /**
     * Mark the job as failed if an exception is thrown that passes a truth-test callback.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     *
     * @throws Throwable
     */
    public function handle($job, callable $next)
    {
        try {
            return $next($job);
        } catch (Throwable $e) {
            if (call_user_func($this->callback, $e, $job) === true) {
                $job->fail($e);
            }

            throw $e;
        }
    }
}
