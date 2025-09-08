<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\InteractsWithTime;

class SessionStore extends TaggableStore implements LockProvider
{
    use InteractsWithTime, RetrievesMultipleKeys;

    /**
     * The session instance.
     *
     * @var \Illuminate\Session\Store
     */
    public $session;

    /**
     * Indicates if values are serialized within the store.
     *
     * @var bool
     */
    protected $serializesValues;

    /**
     * Create a new Session store.
     *
     * @param  \Illuminate\Session\Store  $session
     * @param  bool  $serializesValues
     */
    public function __construct($session, $serializesValues = false)
    {
        $this->session = $session;
        $this->serializesValues = $serializesValues;
    }

    /**
     * Get all of the cached values and their expiration times.
     *
     * @param  bool  $unserialize
     * @return array<string, array{value: mixed, expiresAt: float}>
     */
    public function all($unserialize = true)
    {
        if ($unserialize === false || $this->serializesValues === false) {
            return $this->session->get('_storage', []);
        }

        $storage = [];

        foreach ($this->session->get('_storage', []) as $key => $data) {
            $storage[$key] = [
                'value' => unserialize($data['value']),
                'expiresAt' => $data['expiresAt'],
            ];
        }

        return $storage;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        if ($this->session->missing("_storage.{$key}")) {
            return;
        }

        $item = $this->session->get("_storage.{$key}");

        $expiresAt = $item['expiresAt'] ?? 0;

        if ($expiresAt !== 0 && (Carbon::now()->getPreciseTimestamp(3) / 1000) >= $expiresAt) {
            $this->forget($key);

            return;
        }

        return $this->unserializeValue($item['value']);
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        $this->session->put("_storage.{$key}", [
            'value' => $this->serializeValue($value),
            'expiresAt' => $this->calculateExpiration($seconds),
        ]);

        return true;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        if (! is_null($existing = $this->get($key))) {
            return tap(((int) $existing) + $value, function ($incremented) use ($key) {
                $value = $this->serializeValue($incremented);

                $this->session->put("_storage.{$key}.value", $value);
            });
        }

        $this->forever($key, $value);

        return $value;
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        if ($this->session->exists("_storage.{$key}")) {
            $this->session->forget("_storage.{$key}");

            return true;
        }

        return false;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->session->put('_storage', []);

        return true;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return '';
    }

    /**
     * Get the expiration time of the key.
     *
     * @param  int  $seconds
     * @return float
     */
    protected function calculateExpiration($seconds)
    {
        return $this->toTimestamp($seconds);
    }

    /**
     * Get the UNIX timestamp, with milliseconds, for the given number of seconds in the future.
     *
     * @param  int  $seconds
     * @return float
     */
    protected function toTimestamp($seconds)
    {
        return $seconds > 0 ? (Carbon::now()->getPreciseTimestamp(3) / 1000) + $seconds : 0;
    }

    /**
     * Serialize the given value if necessary.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serializeValue($value)
    {
        return $this->serializesValues ? serialize($value) : $value;
    }

    /**
     * Unserialize the given value if necessary.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserializeValue($value)
    {
        return $this->serializesValues ? unserialize($value) : $value;
    }

    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        return new SessionLock($this, $name, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }
}
