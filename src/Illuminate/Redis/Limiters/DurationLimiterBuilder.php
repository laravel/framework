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
     * Create a new builder instance.
     *
     * @param  \Illuminate\Redis\Connetions\Connection  $connection
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
     * Execute the given callback if a lock is obtained, otherise call the failure callback.
     *
     * @param  callable  $callback
     * @param  callable  $failure
     * @return mixed
     */
    public function then(callable $callback, callable $failure = null)
    {
        try {
            return (new DurationLimiter(
                $this->connection, $this->name, $this->maxLocks, $this->decay
            ))->block($this->timeout, $callback);
        } catch (LimiterTimeoutException $e) {
            if ($failure) {
                return $failure($e);
            }

            throw $e;
        }
    }
}
