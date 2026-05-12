<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Bus\UniqueLock;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Queue\CallQueuedListener;
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
        $cache = Container::getInstance()->make(Cache::class);

        $lock = $cache->lock(
            $this->getLockKey($job), $this->expiresAfter
        );

        if ($lock->get()) {
            try {
                $next($job);
                $this->ensureUniqueJobLockIsReleased($cache, $job);
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
        $this->releaseAfter = $releaseAfter;

        return $this;
    }

    /**
     * Do not release the job back to the queue if no lock can be acquired.
     *
     * @return $this
     */
    public function dontRelease()
    {
        $this->releaseAfter = null;

        return $this;
    }

    /**
     * Set the maximum number of seconds that can elapse before the lock is released.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $expiresAfter
     * @return $this
     */
    public function expireAfter($expiresAfter)
    {
        $this->expiresAfter = $this->secondsUntil($expiresAfter);

        return $this;
    }

    /**
     * Set the prefix of the lock key.
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
     * Indicate that the lock key may be shared across jobs belonging to different classes.
     *
     * @return $this
     */
    public function shared()
    {
        $this->shareKey = true;

        return $this;
    }

    /**
     * Get the lock key for the given job.
     *
     * @param  mixed  $job
     * @return string
     */
    public function getLockKey($job)
    {
        if ($this->shareKey) {
            return $this->prefix.$this->key;
        }

        $jobName = method_exists($job, 'displayName')
            ? hash('xxh128', $job->displayName())
            : get_class($job);

        return $this->prefix.$jobName.':'.$this->key;
    }

    /**
     * Determine if the given job should be unique until processing begins.
     */
    protected function jobShouldBeUniqueUntilProcessing(mixed $job): bool
    {
        return $job instanceof ShouldBeUniqueUntilProcessing ||
            ($job instanceof CallQueuedListener && $job->shouldBeUniqueUntilProcessing());
    }

    /**
     * Ensure the lock for a unique job is released.
     */
    protected function ensureUniqueJobLockIsReleased(Cache $cache, mixed $job): void
    {
        if ($this->jobShouldBeUniqueUntilProcessing($job)) {
            (new UniqueLock($cache))->release($job);
        }
    }
}
