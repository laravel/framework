<?php

namespace Illuminate\Queue;

use Aws\Credentials\CredentialsInterface;
use Closure;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\RejectedPromise;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Throwable;

class AwsCredentialCache
{
    /**
     * The number of seconds before expiration that credentials should be refreshed.
     *
     * @var int
     */
    protected const REFRESH_WINDOW = 60;

    /**
     * The number of seconds the credential refresh lock should be maintained.
     *
     * @var int
     */
    protected const LOCK_SECONDS = 15;

    /**
     * The number of seconds to wait for another process to refresh credentials.
     *
     * @var int
     */
    protected const LOCK_WAIT_SECONDS = 5;

    /**
     * The resolver for the backing cache repository.
     *
     * @var \Closure(): \Illuminate\Contracts\Cache\Repository
     */
    protected $repository;

    /**
     * The resolver for the fallback cache repository, if any.
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
     * Resolve credentials while sharing a single refresh across processes.
     *
     * @param  string  $key
     * @param  callable  $provider
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function resolve($key, callable $provider)
    {
        if ($credentials = $this->freshCredentials($key)) {
            return Create::promiseFor($credentials);
        }

        foreach ($this->repositories() as $repository) {
            try {
                $store = $repository()->getStore();

                if (! $store instanceof LockProvider) {
                    continue;
                }

                $lock = $store->lock($key.':refresh', static::LOCK_SECONDS);

                $lock->block(static::LOCK_WAIT_SECONDS);
            } catch (Throwable) {
                continue;
            }

            if ($credentials = $this->freshCredentials($key)) {
                $this->release($lock);

                return Create::promiseFor($credentials);
            }

            return $this->resolveAndCache($key, $provider, $lock);
        }

        return $this->resolveAndCache($key, $provider);
    }

    /**
     * Get fresh credentials from the available cache repositories.
     *
     * @param  string  $key
     * @return \Aws\Credentials\CredentialsInterface|null
     */
    protected function freshCredentials($key)
    {
        foreach ($this->repositories() as $repository) {
            try {
                $credentials = $repository()->get($key);

                if ($credentials instanceof CredentialsInterface &&
                    (is_null($credentials->getExpiration()) ||
                        $credentials->getExpiration() - time() > static::REFRESH_WINDOW)) {
                    return $credentials;
                }
            } catch (Throwable) {
                //
            }
        }
    }

    /**
     * Resolve and cache credentials before releasing the refresh lock.
     *
     * @param  string  $key
     * @param  callable  $provider
     * @param  \Illuminate\Contracts\Cache\Lock|null  $lock
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    protected function resolveAndCache($key, callable $provider, ?Lock $lock = null)
    {
        try {
            $promise = $provider();
        } catch (Throwable $e) {
            $this->release($lock);

            throw $e;
        }

        return $promise->then(
            function (CredentialsInterface $credentials) use ($key, $lock) {
                $expiration = $credentials->getExpiration();

                if (is_null($expiration)) {
                    $this->put($key, $credentials);
                } elseif (($ttl = $expiration - time() - static::REFRESH_WINDOW) > 0) {
                    $this->put($key, $credentials, $ttl);
                } else {
                    $this->forget($key);
                }

                $this->release($lock);

                return $credentials;
            },
            function ($reason) use ($lock) {
                $this->release($lock);

                return new RejectedPromise($reason);
            },
        );
    }

    /**
     * Store the credentials, silently ignoring unavailable cache stores.
     *
     * @param  string  $key
     * @param  \Aws\Credentials\CredentialsInterface  $credentials
     * @param  int  $ttl
     * @return void
     */
    protected function put($key, CredentialsInterface $credentials, $ttl = 0)
    {
        foreach ($this->repositories() as $repository) {
            try {
                $ttl > 0
                    ? $repository()->put($key, $credentials, (int) $ttl)
                    : $repository()->forever($key, $credentials);
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
    protected function forget($key)
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

    /**
     * Release the credential refresh lock without affecting credential resolution.
     *
     * @param  \Illuminate\Contracts\Cache\Lock|null  $lock
     * @return void
     */
    protected function release(?Lock $lock)
    {
        try {
            $lock?->release();
        } catch (Throwable) {
            //
        }
    }
}
