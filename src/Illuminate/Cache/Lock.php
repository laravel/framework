<?php

namespace Illuminate\Cache;

use Illuminate\Support\Str;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Contracts\Cache\Lock as LockContract;
use Illuminate\Contracts\Cache\LockTimeoutException;

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
    protected $owner;

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
     * Secures this lock against out of order releases of expired clients via assigning an owner.
     *
     * @return Lock
     */
    public function owned()
    {
        return $this->setOwner(Str::random());
    }

    /**
     * Secures this lock against out of order releases of expired clients via assigning an owner.
     *
     * @param  string $owner
     * @return Lock
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Determines whether this is a client scoped lock.
     *
     * @return bool
     */
    protected function isOwned()
    {
        return ! is_null($this->owner);
    }

    /**
     * Returns the value that should be written into the cache.
     *
     * @return mixed
     */
    protected function value()
    {
        return $this->isOwned() ? $this->owner : 1;
    }

    /**
     * Determines whether this lock is allowed to release the lock in the driver.
     *
     * @return bool
     */
    protected function isOwnedByCurrentProcess()
    {
        if (! $this->isOwned()) {
            return true;
        }

        return $this->getValue() === $this->owner;
    }
}
