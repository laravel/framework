<?php

namespace Illuminate\Queue\Middleware;

use Closure;
use Throwable;

class FailOnException
{
    /**
     * The truth-test callback to determine if the job should stop retries.
     *
     * @var \Closure(\Throwable, mixed): bool
     */
    protected Closure $callback;

    /**
     * Create a middleware instance.
     *
     * @param  (\Closure(\Throwable, mixed): bool)|array<int, class-string<\Throwable>>  $callback
     */
    public function __construct($callback)
    {
        if (is_array($callback)) {
            $callback = $this->failForExceptions($callback);
        }

        $this->callback = $callback;
    }

    /**
     * @param  array<int, class-string<\Throwable>>  $exceptions
     * @return \Closure(\Throwable, mixed): bool
     */
    protected function failForExceptions(array $exceptions)
    {
        return static function (Throwable $throwable) use ($exceptions) {
            foreach ($exceptions as $exception) {
                if ($throwable instanceof $exception) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
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
