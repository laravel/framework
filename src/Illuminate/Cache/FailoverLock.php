<?php

namespace Illuminate\Cache;

use Closure;

class FailoverLock extends Lock
{
    /**
     * The underlying lock that was acquired.
     *
     * @var \Illuminate\Contracts\Cache\Lock|null
     */
    protected $lock;

    /**
     * Create a new lock instance.
     *
     * @param  \Closure(\Closure): mixed  $attempt
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     */
    public function __construct(protected Closure $attempt, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        return ($this->attempt)(function ($store) {
            $lock = $store->lock($this->name, $this->seconds, $this->owner);

            if ($acquired = $lock->acquire()) {
                $this->lock = $lock;
            }

            return $acquired;
        });
    }

    /**
     * Attempt to refresh the lock for the given number of seconds.
     *
     * @param  int|null  $seconds
     * @return bool
     */
    public function refresh($seconds = null)
    {
        return $this->onLock(fn ($lock) => $lock->refresh($seconds));
    }

    /**
     * Release the lock.
     *
     * @return bool
     */
    public function release()
    {
        return $this->onLock(fn ($lock) => $lock->release());
    }

    /**
     * Releases this lock in disregard of ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->onLock(fn ($lock) => $lock->forceRelease());
    }

    /**
     * Determine if the lock is currently held by any process.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->onLock(fn ($lock) => $lock->isLocked());
    }

    /**
     * Determine whether this lock is owned by the given identifier.
     *
     * @param  string|null  $owner
     * @return bool
     */
    public function isOwnedBy($owner)
    {
        return $this->onLock(fn ($lock) => $lock->isOwnedBy($owner));
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return string|null
     */
    protected function getCurrentOwner()
    {
        return $this->isOwnedByCurrentProcess() ? $this->owner : null;
    }

    /**
     * Run the callback on the acquired lock, or on the first available store's lock.
     *
     * @param  \Closure(\Illuminate\Contracts\Cache\Lock): mixed  $callback
     * @return mixed
     */
    protected function onLock(Closure $callback)
    {
        if ($this->lock) {
            return $callback($this->lock);
        }

        return ($this->attempt)(
            fn ($store) => $callback($store->lock($this->name, $this->seconds, $this->owner))
        );
    }
}
