<?php

namespace Illuminate\Contracts\Cache;

interface Lock
{
    /**
     * Attempt to acquire the lock.
     *
     * @param  callable|null  $callback
     * @return bool
     */
    public function get($callback = null);

    /**
     * Attempt to acquire the lock while blocking indefinitely.
     *
     * @param  callable|null  $calback
     * @return bool
     */
    public function block($callback = null);

    /**
     * Attempt to acquire the lock for the given number of seconds.
     *
     * @param  int  $seconds
     * @param  callable|null  $callback
     * @return bool
     */
    public function blockFor($seconds, $callback = null);

    /**
     * Release the lock.
     *
     * @return void
     */
    public function release();
}
