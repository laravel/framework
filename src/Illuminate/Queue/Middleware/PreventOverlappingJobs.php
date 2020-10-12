<?php

namespace Illuminate\Queue\Middleware;

use Illuminate\Contracts\Cache\Repository as Cache;

class PreventOverlappingJobs
{
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
    public $prefix;

    /**
     * Create a new overlapping jobs middleware instance.
     *
     * @param  string  $key
     * @param  int  $expiresAt
     * @param  string  $prefix
     *
     * @return void
     */
    public function __construct($key = '', $prefix = 'overlap:')
    {
        $this->key = $key;
        $this->prefix = $prefix;
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
        $lock = app(Cache::class)->lock($this->getLockKey($job));

        if ($lock->get()) {
            try {
                $next($job);
            } finally {
                $lock->release();
            }
        }
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
