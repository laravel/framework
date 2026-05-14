<?php

namespace Illuminate\Cache;

use Exception;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\InteractsWithTime;

class StorageStore implements Store
{
    use InteractsWithTime, RetrievesMultipleKeys;

    /**
     * The filesystem disk instance.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $disk;

    /**
     * The storage path where cache files should be written.
     *
     * @var string
     */
    protected $directory;

    /**
     * A string that should be prepended to keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The classes that should be allowed during unserialization.
     *
     * @var array|bool|null
     */
    protected $serializableClasses;

    /**
     * Create a new storage cache store instance.
     *
     * @param  \Illuminate\Contracts\Filesystem\Filesystem  $disk
     * @param  string  $directory
     * @param  string  $prefix
     * @param  array|bool|null  $serializableClasses
     */
    public function __construct(Filesystem $disk, $directory = '', $prefix = '', $serializableClasses = null)
    {
        $this->disk = $disk;
        $this->directory = trim($directory, '/');
        $this->prefix = $prefix;
        $this->serializableClasses = $serializableClasses;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->getPayload($key)['data'] ?? null;
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
        return $this->disk->put(
            $this->path($key), $this->expiration($seconds).serialize($value)
        );
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds)
    {
        if (! is_null($this->get($key))) {
            return false;
        }

        return $this->put($key, $value, $seconds);
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
        $raw = $this->getPayload($key);

        return tap(((int) $raw['data']) + $value, function ($newValue) use ($key, $raw) {
            $this->put($key, $newValue, $raw['time'] ?? 0);
        });
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
     * Adjust the expiration time of a cached item.
     *
     * @param  string  $key
     * @param  int  $seconds
     * @return bool
     */
    public function touch($key, $seconds)
    {
        $payload = $this->getPayload($key);

        if (is_null($payload['data'])) {
            return false;
        }

        return $this->put($key, $payload['data'], $seconds);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        $forgotten = $this->disk->delete($this->path($key));

        if ($forgotten) {
            $this->disk->delete($this->path("illuminate:cache:flexible:created:{$key}"));
        }

        return $forgotten;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        if ($this->directory === '') {
            $files = $this->disk->allFiles();

            return $files === [] || $this->disk->delete($files);
        }

        return $this->disk->deleteDirectory($this->directory)
            && $this->disk->makeDirectory($this->directory);
    }

    /**
     * Retrieve an item and expiry time from the cache by key.
     *
     * @param  string  $key
     * @return array
     */
    protected function getPayload($key)
    {
        $path = $this->path($key);

        try {
            if (is_null($contents = $this->disk->get($path))) {
                return $this->emptyPayload();
            }

            $expire = substr($contents, 0, 10);
        } catch (Exception) {
            return $this->emptyPayload();
        }
        if ($this->currentTime() >= $expire) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        try {
            $data = $this->unserialize(substr($contents, 10));
        } catch (Exception) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        $time = $expire - $this->currentTime();

        return compact('data', 'time');
    }

    /**
     * Unserialize the given value.
     *
     * @param  string  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        if ($this->serializableClasses !== null) {
            return unserialize($value, ['allowed_classes' => $this->serializableClasses]);
        }

        return unserialize($value);
    }

    /**
     * Get a default empty payload for the cache.
     *
     * @return array
     */
    protected function emptyPayload()
    {
        return ['data' => null, 'time' => null];
    }

    /**
     * Get the full path for the given cache key.
     *
     * @param  string  $key
     * @return string
     */
    public function path($key)
    {
        $parts = array_slice(str_split($hash = sha1($this->prefix.$key), 2), 0, 2);

        return trim($this->directory.'/'.implode('/', $parts).'/'.$hash, '/');
    }

    /**
     * Get the expiration time based on the given seconds.
     *
     * @param  int  $seconds
     * @return int
     */
    protected function expiration($seconds)
    {
        $time = $this->availableAt($seconds);

        return $seconds === 0 || $time > 9999999999 ? 9999999999 : $time;
    }

    /**
     * Get the filesystem disk instance.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function getDisk()
    {
        return $this->disk;
    }

    /**
     * Get the working directory of the cache.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the cache key prefix.
     *
     * @param  string  $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
