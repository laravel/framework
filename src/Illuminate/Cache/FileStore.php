<?php

namespace Illuminate\Cache;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;

class FileStore implements Store
{
    use RetrievesMultipleKeys;

    /**
     * The Illuminate Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The file cache directory.
     *
     * @var string
     */
    protected $directory;

    /**
     * Create a new file cache store instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $directory
     * @return void
     */
    public function __construct(Filesystem $files, $directory)
    {
        $this->files = $files;
        $this->directory = $directory;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        return Arr::get($this->getPayload($key), 'data');
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        $this->ensureCacheDirectoryExists($path = $this->path($key));

        $this->files->put(
            $path, $this->expiration($minutes).serialize($value), true
        );
    }

    /**
     * Create the file cache directory if necessary.
     *
     * @param  string  $path
     * @return void
     */
    protected function ensureCacheDirectoryExists($path)
    {
        if (! $this->files->exists(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return int
     */
    public function increment($key, $value = 1)
    {
        $raw = $this->getPayload($key);

        return tap(((int) $raw['data']) + $value, function ($newValue) use ($key, $raw) {
            $this->put($key, $newValue, $raw['time']);
        });
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
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
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->put($key, $value, 0);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        if ($this->files->exists($file = $this->path($key))) {
            return $this->files->delete($file);
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
        if (! $this->files->isDirectory($this->directory)) {
            return false;
        }

        foreach ($this->files->directories($this->directory) as $directory) {
            if (! $this->files->deleteDirectory($directory)) {
                return false;
            }
        }

        return true;
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

        // If the file doesn't exists, we obviously can't return the cache so we will
        // just return null. Otherwise, we'll get the contents of the file and get
        // the expiration UNIX timestamps from the start of the file's contents.
        try {
            $expire = substr(
                $contents = $this->files->get($path, true), 0, 10
            );
        } catch (Exception $e) {
            return $this->emptyPayload();
        }

        // If the current time is greater than expiration timestamps we will delete
        // the file and return null. This helps clean up the old files and keeps
        // this directory much cleaner for us as old files aren't hanging out.
        if (Carbon::now()->getTimestamp() >= $expire) {
            $this->forget($key);

            return $this->emptyPayload();
        }

        $data = unserialize(substr($contents, 10));

        // Next, we'll extract the number of minutes that are remaining for a cache
        // so that we can properly retain the time for things like the increment
        // operation that may be performed on this cache on a later operation.
        $time = ($expire - Carbon::now()->getTimestamp()) / 60;

        return compact('data', 'time');
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
    protected function path($key)
    {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);

        return $this->directory.'/'.implode('/', $parts).'/'.$hash;
    }

    /**
     * Get the expiration time based on the given minutes.
     *
     * @param  float|int  $minutes
     * @return int
     */
    protected function expiration($minutes)
    {
        $time = Carbon::now()->getTimestamp() + (int) ($minutes * 60);

        return $minutes === 0 || $time > 9999999999 ? 9999999999 : (int) $time;
    }

    /**
     * Get the Filesystem instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
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
        return '';
    }
}
