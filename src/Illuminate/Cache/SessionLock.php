<?php

namespace Illuminate\Cache;

use Illuminate\Support\Carbon;

class SessionLock extends Lock
{
    /**
     * The parent session cache store.
     *
     * @var \Illuminate\Cache\SessionStore
     */
    protected $store;

    /**
     * Create a new lock instance.
     *
     * @param  \Illuminate\Cache\SessionStore  $store
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     */
    public function __construct($store, $name, $seconds, $owner = null)
    {
        parent::__construct($name, $seconds, $owner);

        $this->store = $store;
    }

    /**
     * Attempt to acquire the lock.
     *
     * @return bool
     */
    public function acquire()
    {
        $expiration = $this->store->session->get("{$this->name}.expiresAt");

        $expiration ??= Carbon::now()->addSecond();

        if ($this->exists() && $expiration->isFuture()) {
            return false;
        }

        $this->store->session->put($this->name, [
            'owner' => $this->owner,
            'expiresAt' => $this->seconds === 0 ? null : Carbon::now()->addSeconds($this->seconds),
        ]);

        return true;
    }

    /**
     * Determine if the current lock exists.
     *
     * @return bool
     */
    protected function exists()
    {
        return $this->store->session->exists($this->name);
    }

    /**
     * Release the lock.
     *
     * @return bool
     */
    public function release()
    {
        if (! $this->exists()) {
            return false;
        }

        if (! $this->isOwnedByCurrentProcess()) {
            return false;
        }

        $this->forceRelease();

        return true;
    }

    /**
     * Returns the owner value written into the driver for this lock.
     *
     * @return string|null
     */
    protected function getCurrentOwner()
    {
        if (! $this->exists()) {
            return null;
        }

        return $this->store->session->get("{$this->name}.owner");
    }

    /**
     * Releases this lock regardless of ownership.
     *
     * @return void
     */
    public function forceRelease()
    {
        $this->store->session->forget($this->name);
    }
}
