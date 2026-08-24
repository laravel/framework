<?php

namespace Illuminate\Queue;

use Aws\CacheInterface;
use Closure;
use Throwable;

class AwsCredentialCache implements CacheInterface
{
    /**
     * The resolver for the backing cache repository.
     *
     * The repository is resolved lazily on first use so that building a
     * connection never touches the cache while the application is still
     * bootstrapping.
     *
     * @var \Closure(): \Illuminate\Contracts\Cache\Repository
     */
    protected $repository;

    /**
     * The resolver for the fallback cache repository, if any.
     *
     * The fallback is written through on every set so that it is already warm
     * when the primary store becomes unavailable.
     *
     * @var \Closure(): \Illuminate\Contracts\Cache\Repository|null
     */
    protected $fallback;

    /**
     * Create a new AWS credential cache.
     *
     * @param  \Closure(): \Illuminate\Contracts\Cache\Repository  $repository
     * @param  \Closure(): \Illuminate\Contracts\Cache\Repository|null  $fallback
     */
    public function __construct(Closure $repository, ?Closure $fallback = null)
    {
        $this->repository = $repository;
        $this->fallback = $fallback;
    }

    /**
     * Get the cached credentials, treating an unavailable cache store as a miss.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        foreach ($this->repositories() as $repository) {
            try {
                if (! is_null($value = $repository()->get($key))) {
                    return $value;
                }
            } catch (Throwable) {
                //
            }
        }

        return null;
    }

    /**
     * Store the credentials, silently ignoring unavailable cache stores.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $ttl
     * @return void
     */
    public function set($key, $value, $ttl = 0)
    {
        foreach ($this->repositories() as $repository) {
            try {
                $ttl > 0
                    ? $repository()->put($key, $value, (int) $ttl)
                    : $repository()->forever($key, $value);
            } catch (Throwable) {
                //
            }
        }
    }

    /**
     * Remove the cached credentials, silently ignoring unavailable cache stores.
     *
     * @param  string  $key
     * @return void
     */
    public function remove($key)
    {
        foreach ($this->repositories() as $repository) {
            try {
                $repository()->forget($key);
            } catch (Throwable) {
                //
            }
        }
    }

    /**
     * Get the cache repository resolvers in order of preference.
     *
     * @return array<int, \Closure(): \Illuminate\Contracts\Cache\Repository>
     */
    protected function repositories()
    {
        return array_filter([$this->repository, $this->fallback]);
    }
}
