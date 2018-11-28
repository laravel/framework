<?php

namespace Illuminate\Cache;

use Illuminate\Support\InteractsWithTime;
use Illuminate\Contracts\Cache\Lock as LockContract;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Str;

abstract class Lock implements LockContract
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
     * A (usually) random string that acts as scope identifier of this lock.
     *
     * @var string
     */
    protected $scope;

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
     * Release the lock.
     *
     * @return void
     */
    abstract public function release();

    /**
     * Returns the value written into the driver for this lock.
     *
     * @return mixed
     */
    abstract protected function getValue();

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
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return $result;
    }

    /**
     * Attempt to acquire the lock for the given number of seconds.
     *
     * @param  int  $seconds
     * @param  callable|null  $callback
     * @return bool
     *
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

    /**
     * Secures this lock against out of order releases of expired clients.
     *
     * @return Lock
     */
    public function safe()
    {
        return $this->scoped(Str::random());
    }

    /**
     * Secures this lock against out of order releases of expired clients.
     *
     * @param  string $scope
     * @return Lock
     */
    public function scoped($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Determines whether this is a client scoped lock.
     *
     * @return bool
     */
    protected function isScoped()
    {
        return ! is_null($this->scope);
    }

    /**
     * Returns the value that should be written into the cache.
     *
     * @return mixed
     */
    protected function value()
    {
        return $this->isScoped() ? $this->scope : 1;
    }

    /**
     * Determines whether this lock is allowed to release the lock in the driver.
     *
     * @return bool
     */
    protected function canRelease()
    {
        if (! $this->isScoped()) {
            return true;
        }

        return $this->getValue() === $this->scope;
    }
}
