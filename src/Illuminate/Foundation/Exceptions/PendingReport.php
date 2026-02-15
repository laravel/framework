<?php

namespace Illuminate\Foundation\Exceptions;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactoryContract;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Support\Traits\Conditionable;
use Throwable;

class PendingReport
{
    use Conditionable;

    /**
     * Create a new Pending Report instance.
     */
    public function __construct(
        protected ContainerContract $container,
        protected CacheFactoryContract $cache,
        protected ExceptionHandlerContract $handler,
        protected Throwable $exception,
        protected ?string $store = null,
        protected int $times = 1,
    )
    {
        //
    }

    /**
     * Reports the exception.
     *
     * @return void
     */
    protected function report()
    {
        $this->handler->report($this->exception);
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
     * Reports the exception once during a window of time.
     *
     * @param \DateTimeInterface|\DateInterval|int $seconds
     * @return void
     */
    public function every($seconds = 60)
    {
        $this->limiter()->attempt(
            'illuminate:report_attempt:' . get_class($this->exception), $this->times, $this->report(...), $seconds
        );
    }
}
