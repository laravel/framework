<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\InteractsWithTime;

class WithoutOverlapping
{
    use InteractsWithTime;

    /**
     * The job's unique key used for preventing overlaps.
     *
     * @var string
     */
    public $key;

    /**
     * The number of seconds before a job should be available again if no lock was acquired.
     *
     * @var \DateTimeInterface|int|null
     */
    public $releaseAfter;

    /**
     * The number of seconds before the lock should expire.
     *
     * @var int
     */
    public $expiresAfter;

    /**
     * The prefix of the lock key.
     *
     * @var string
     */
    public $prefix = 'laravel-queue-overlap:';

    /**
     * Share the key across different jobs.
     *
     * @var bool
     */
    public $shareKey = false;

    /**
     * Create a new middleware instance.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|int|null  $releaseAfter
     * @param  \DateTimeInterface|int  $expiresAfter
     * @return void
     */
    public function __construct($key = '', $releaseAfter = 0, $expiresAfter = 0)
    {
        $this->key = $key;
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
        $lock = Container::getInstance()->make(Cache::class)->lock(
            $this->getLockKey($job), $this->expiresAfter
        );

        if ($lock->get()) {
            try {
                $next($job);
            } finally {
                $lock->release();
            }
        } elseif (! is_null($this->releaseAfter)) {
            $job->release($this->releaseAfter);
        }
    }

    /**
     * Set the delay (in seconds) to release the job back to the queue.
     *
     * @param  \DateTimeInterface|int  $releaseAfter
     * @return $this
     */
    public function releaseAfter($releaseAfter)
    {
        return tap($this, fn () => $this->releaseAfter = $releaseAfter);
    }

    /**
     * Do not release the job back to the queue if no lock can be acquired.
     *
     * @return $this
     */
    public function dontRelease()
    {
        return tap($this, fn () => $this->releaseAfter = null);
    }

    /**
     * Set the maximum number of seconds that can elapse before the lock is released.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $expiresAfter
     * @return $this
     */
    public function expireAfter($expiresAfter)
    {
        return tap($this, fn () => $this->expiresAfter = $this->secondsUntil($expiresAfter));
    }

    /**
     * Set the prefix of the lock key.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function withPrefix(string $prefix)
    {
        return tap($this, fn () => $this->prefix = $prefix);
    }

    /**
     * Indicate that the lock key should be shared across job classes.
     *
     * @return $this
     */
    public function shared()
    {
        return tap($this, fn () => $this->shareKey = true);
    }

    /**
     * Get the lock key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    public function getLockKey($job)
    {
        return $this->shareKey
            ? $this->prefix.$this->key
            : $this->prefix.get_class($job).':'.$this->key;
    }
}
