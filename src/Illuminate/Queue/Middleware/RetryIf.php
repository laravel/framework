<?php

namespace Illuminate\Queue\Middleware;

use Closure;
use Throwable;

class RetryIf
{
    /**
     * @param  \Closure(\Throwable, ?mixed): bool  $retryIf  The condition of the failure that will retry the job.
     */
    public function __construct(protected Closure $retryIf)
    {
    }

    /**
     * @param  class-string<\Throwable>  ...$exceptions
     * @return static
     */
    public static function failForExceptions(...$exceptions): static
    {
        return new static(static function (Throwable $throwable) use ($exceptions) {
            foreach ($exceptions as $exception) {
                if ($throwable instanceof $exception) {
                    return false;
                }
            }

            return true;
        });
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
            if (call_user_func($this->retryIf, $e, $job) !== true) {
                $job->fail($e);
            }

            throw $e;
        }
    }
}
