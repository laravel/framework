<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Contracts\Cache\Repository as Cache;

class PreventOverlappingJobs
{
    /**
     * The amount of time (in seconds) to expire the lock.
     *
     * @var int
     */
    public $expiresAt;

    /**
     * The key of the job.
     *
     * @var string
     */
    public $key;

    /**
     * The prefix of the lock key.
     *
     * @var string
     */
    public $prefix = 'overlap:';

    /**
     * The delay (in seconds) to release the job back to the queue.
     *
     * @var int|null
     */
    public $releaseAfter;

    /**
     * Create a new overlapping jobs middleware instance.
     *
     * @param  string  $key
     * @param  int|null  $releaseAfter
     * @param  int  $expiresAt
     *
     * @return void
     */
    public function __construct($key = '', $releaseAfter = 0, $expiresAt = 0)
    {
        $this->key = $key;
        $this->releaseAfter = $releaseAfter;
        $this->expiresAt = $expiresAt;
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
        $lock = app(Cache::class)->lock($this->getLockKey($job), $this->expiresAt);

        if ($lock->get()) {
            try {
                $next($job);
            } finally {
                $lock->release();
            }
        } elseif (!is_null($this->releaseAfter)) {
            $job->release($this->releaseAfter);
        }
    }

    /**
     * Do not release the job back to the queue.
     *
     * @return $this
     */
    public function dontRelease()
    {
        $this->releaseAfter = null;

        return $this;
    }

    /**
     * Set the expiry (in seconds) of the lock key.
     *
     * @param  int  $expiresAt
     * @return $this
     */
    public function expireAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Set the delay (in seconds) to release the job back to the queue.
     *
     * @param  int  $releaseAfter
     * @return $this
     */
    public function releaseAfter($releaseAfter)
    {
        $this->releaseAfter = $releaseAfter;

        return $this;
    }

    /**
     * Set the prefix of the lock key.
     *
     * @param  string  $prefix
     * @return $this
     */
    public function withPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the lock key.
     *
     * @param  mixed  $job
     * @return string
     */
    public function getLockKey($job)
    {
        return $this->prefix.get_class($job).':'.$this->key;
    }
}
