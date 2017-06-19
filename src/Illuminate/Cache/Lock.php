<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\LockTimeoutException;

abstract class Lock
{
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
     */
    public function block($seconds, $callback = null)
    {
        $starting = time();

        while (! $this->acquire()) {
            usleep(250 * 1000);

            if (time() - $seconds >= $starting) {
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
