<?php

namespace Illuminate\Foundation\Exceptions;

use Exception;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactoryContract;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Support\Traits\Conditionable;

class PendingReport
{
    use Conditionable;

    /**
     * The exception to report.
     *
     * @var \Throwable
     */
    protected $exception;

    /**
     * The cache store repository to use.
     *
     * @var string|null
     */
    protected $store;

    /**
     * Maximum limit to report the same exception.
     *
     * @var int
     */
    protected int $times = 1;

    /**
     * Create a new Pending Report instance.
     */
    public function __construct(
        protected ContainerContract $container,
        protected CacheFactoryContract $cache,
        protected ExceptionHandlerContract $handler,
    ) {
        //
    }

    /**
     * Returns the Rate Limiter using the cache store repository.
     *
     * @return \Illuminate\Cache\RateLimiter
     */
    protected function limiter()
    {
        return $this->container->make(RateLimiter::class, [
            'cache' => $this->cache->store($this->store),
        ]);
    }

    /**
     * Sets the exception to report.
     *
     * @param  \Throwable|string  $exception
     * @return $this
     */
    public function exception($exception)
    {
        if (is_string($exception)) {
            $exception = new Exception($exception);
        }

        $this->exception = $exception;

        return $this;
    }

    /**
     * Sets the Cache Store to limit the exception report.
     *
     * @param  string|null  $store
     * @return $this
     */
    public function using($store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Reports the exception a limited number of times.
     *
     * @param  int  $times
     * @return $this
     */
    public function atMost($times)
    {
        $this->times = $times;

        return $this;
    }

    /**
     * Reports the exception a number of times during a window of time.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $seconds
     * @return void
     */
    public function every($seconds)
    {
        $this->limiter()->attempt(
            'illuminate:report:'.get_class($this->exception), $this->times, $this->now(...), $seconds,
        );
    }

    /**
     * Reports the exception immediately.
     *
     * @return void
     */
    public function now()
    {
        $this->handler->report($this->exception);
    }
}
