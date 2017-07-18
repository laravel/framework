<?php

namespace Illuminate\Cache;

use Illuminate\Support\InteractsWithTime;
use Illuminate\Contracts\Cache\LockTimeoutException;

abstract class Lock
{
    use InteractsWithTime;

    /**
     * The name of the lock.
     *
     * @var string
     */
    protected $name;

    /**
     * The number of seconds the lock should be maintained.
     *
     * @var int
     */
    protected $seconds;

    /**
     * Create a new lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @return void
     */
    public function __construct($name, $seconds)
    {
        $this->name = $name;
        $this->seconds = $seconds;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    abstract public function acquire();

    /**
     * Attempt to acquire the lock.
     *
     * @param  callable|null  $callback
     * @return bool
     */
    public function get($callback = null)
    {
        $result = $this->acquire();

        if ($result && is_callable($callback)) {
            return tap($callback(), function () {
                $this->release();
            });
        }

        return $result;
    }

    /**
     * Attempt to acquire the lock for the given number of seconds.
     *
     * @param  int  $seconds
     * @param  callable|null  $callback
     * @return bool
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function block($seconds, $callback = null)
    {
        $starting = $this->currentTime();

        while (! $this->acquire()) {
            usleep(250 * 1000);

            if ($this->currentTime() - $seconds >= $starting) {
                throw new LockTimeoutException;
            }
        }

        if (is_callable($callback)) {
            return tap($callback(), function () {
                $this->release();
            });
        }

        return true;
    }
}
