<?php

namespace Illuminate\Redis\Limiters;

use Illuminate\Support\InteractsWithTime;
use Illuminate\Contracts\Redis\LimiterTimeoutException;

class DurationLimiterBuilder
{
    use InteractsWithTime;

    /**
     * The Redis connection.
     *
     * @var \Illuminate\Redis\Connections\Connection
     */
    public $connection;

    /**
     * The name of the lock.
     *
     * @var string
     */
    public $name;

    /**
     * The maximum number of locks that can obtained per time window.
     *
     * @var int
     */
    public $maxLocks;

    /**
     * The amount of time the lock window is maintained.
     *
     * @var int
     */
    public $decay;

    /**
     * The amount of time to block until a lock is available.
     *
     * @var int
     */
    public $timeout = 3;

    /**
     * Whether to calculate max locks per second.
     *
     * @var bool
     */
    public $smooth = false;

    /**
     * Create a new builder instance.
     *
     * @param  \Illuminate\Redis\Connections\Connection  $connection
     * @param  string  $name
     * @return void
     */
    public function __construct($connection, $name)
    {
        $this->name = $name;
        $this->connection = $connection;
    }

    /**
     * Set the maximum number of locks that can obtained per time window.
     *
     * @param  int  $maxLocks
     * @return $this
     */
    public function allow($maxLocks)
    {
        $this->maxLocks = $maxLocks;

        return $this;
    }

    /**
     * Set the amount of time the lock window is maintained.
     *
     * @param  int  $decay
     * @return $this
     */
    public function every($decay)
    {
        $this->decay = $this->secondsUntil($decay);

        return $this;
    }

    /**
     * Set the amount of time to block until a lock is available.
     *
     * @param  int  $timeout
     * @return $this
     */
    public function block($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Set smooth limiting flag.
     *
     * @param bool $enable
     * @return $this
     */
    public function smoothly($enable = true)
    {
        $this->smooth = $enable;

        return $this;
    }

    /**
     * Execute the given callback if a lock is obtained, otherwise call the failure callback.
     *
     * @param  callable  $callback
     * @param  callable|null  $failure
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Redis\LimiterTimeoutException
     */
    public function then(callable $callback, callable $failure = null)
    {
        try {
            $maxLocks = $this->maxLocks;
            $decay = $this->decay;

            if ($this->smooth) {
                $locksPerSecond = $maxLocks / $decay;

                if ($locksPerSecond < 1) {
                    $maxLocks = 1;
                    $decay = (int) floor(1 / $locksPerSecond);
                } else {
                    $maxLocks = (int) floor($locksPerSecond);
                    $decay = 1;
                }
            }

            return (new DurationLimiter(
                $this->connection, $this->name, $maxLocks, $decay
            ))->block($this->timeout, $callback);
        } catch (LimiterTimeoutException $e) {
            if ($failure) {
                return $failure($e);
            }

            throw $e;
        }
    }
}
