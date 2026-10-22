<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\InteractsWithTime;

use function Illuminate\Support\enum_value;

class ConcurrencyLimited
{
    use InteractsWithTime;

    /**
     * The job's unique key used for limiting concurrency.
     *
     * @var string
     */
    public $key;

    /**
     * The maximum number of jobs that may run at the same time.
     *
     * @var int
     */
    public $limit;

    /**
     * The number of seconds before a job should be available again if no slot was available.
     *
     * @var \DateTimeInterface|int|null
     */
    public $releaseAfter;

    /**
     * The number of seconds before a slot should expire.
     *
     * @var int
     */
    public $expiresAfter;

    /**
     * The prefix of the slot key.
     *
     * @var string
     */
    public $prefix = 'laravel-queue-concurrency:';

    /**
     * Share the key across different jobs.
     *
     * @var bool
     */
    public $shareKey = false;

    /**
     * Create a new middleware instance.
     *
     * @param  \UnitEnum|string  $key
     * @param  int  $limit
     * @param  \DateTimeInterface|int|null  $releaseAfter
     * @param  \DateTimeInterface|int  $expiresAfter
     */
    public function __construct($key = '', $limit = 1, $releaseAfter = 0, $expiresAfter = 0)
    {
        $this->key = enum_value($key);
        $this->limit = $limit;
        $this->releaseAfter = $releaseAfter;
        $this->expiresAfter = $this->secondsUntil($expiresAfter);
    }

    /**
     * Process the job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
        $running = false;

        return Container::getInstance()->make(Cache::class)
            ->funnel($this->getLimiterKey($job).':')
            ->limit($this->limit)
            ->releaseAfter($this->expiresAfter)
            ->block(0)
            ->then(function () use ($job, $next, &$running) {
                $running = true;

                return $next($job);
            }, function ($e) use ($job, &$running) {
                if ($running) {
                    throw $e;
                }

                if (! is_null($this->releaseAfter)) {
                    $job->release($this->releaseAfter);
                }
            });
    }

    /**
     * Set the delay (in seconds) to release the job back to the queue if no slot is available.
     *
     * @param  \DateTimeInterface|int  $releaseAfter
     * @return $this
     */
    public function releaseAfter($releaseAfter)
    {
        $this->releaseAfter = $releaseAfter;

        return $this;
    }

    /**
     * Do not release the job back to the queue if no slot is available.
     *
     * @return $this
     */
    public function dontRelease()
    {
        $this->releaseAfter = null;

        return $this;
    }

    /**
     * Set the maximum number of seconds that can elapse before a slot is released.
     *
     * @param  \DateTimeInterface|int  $expiresAfter
     * @return $this
     */
    public function expireAfter($expiresAfter)
    {
        $this->expiresAfter = $this->secondsUntil($expiresAfter);

        return $this;
    }

    /**
     * Set the prefix of the slot key.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function withPrefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Indicate that the slots should be shared across job classes.
     *
     * @return $this
     */
    public function shared()
    {
        $this->shareKey = true;

        return $this;
    }

    /**
     * Get the limiter key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    public function getLimiterKey($job)
    {
        if ($this->shareKey) {
            return $this->prefix.$this->key;
        }

        $jobName = method_exists($job, 'displayName')
            ? hash('xxh128', $job->displayName())
            : get_class($job);

        return $this->prefix.$jobName.':'.$this->key;
    }
}
