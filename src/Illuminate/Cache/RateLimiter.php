<?php

namespace Illuminate\Cache;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\InteractsWithTime;

use function Illuminate\Support\enum_value;

class RateLimiter
{
    use InteractsWithTime;

    /**
     * The cache store implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The configured limit object resolvers.
     *
     * @var array
     */
    protected $limiters = [];

    /**
     * Create a new rate limiter instance.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Register a named rate limiter configuration.
     *
     * @param  \UnitEnum|string  $name
     * @param  \Closure  $callback
     * @return $this
     */
    public function for($name, Closure $callback)
    {
        $resolvedName = $this->resolveLimiterName($name);

        $this->limiters[$resolvedName] = $callback;

        return $this;
    }

    /**
     * Get the given named rate limiter.
     *
     * @param  \UnitEnum|string  $name
     * @return \Closure|null
     */
    public function limiter($name)
    {
        $resolvedName = $this->resolveLimiterName($name);

        $limiter = $this->limiters[$resolvedName] ?? null;

        if (! is_callable($limiter)) {
            return;
        }

        return function (...$args) use ($limiter) {
            $result = $limiter(...$args);

            if (! is_array($result)) {
                return $result;
            }

            $duplicates = (new Collection($result))->duplicates('key');

            if ($duplicates->isEmpty()) {
                return $result;
            }

            foreach ($result as $limit) {
                if ($duplicates->contains($limit->key)) {
                    $limit->key = $limit->fallbackKey();
                }
            }

            return $result;
        };
    }

    /**
     * Attempts to execute a callback if it's not limited.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  \Closure  $callback
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return mixed
     */
    public function attempt($key, $maxAttempts, Closure $callback, $decaySeconds = 60, $slidingWindow = false)
    {
        if ($this->tooManyAttempts($key, $maxAttempts, $decaySeconds, $slidingWindow)) {
            return false;
        }

        if (is_null($result = $callback())) {
            $result = true;
        }

        return tap($result, function () use ($key, $decaySeconds, $slidingWindow) {
            $this->hit($key, $decaySeconds, $slidingWindow);
        });
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return bool
     */
    public function tooManyAttempts($key, $maxAttempts, $decaySeconds = 60, $slidingWindow = false)
    {
        if ($slidingWindow) {
            return $this->slidingWindowTooManyAttempts($key, $maxAttempts, $decaySeconds);
        }

        if ($this->attempts($key) >= $maxAttempts) {
            if ($this->cache->has($this->cleanRateLimiterKey($key).':timer')) {
                return true;
            }

            $this->resetAttempts($key);
        }

        return false;
    }

    /**
     * Increment (by 1) the counter for a given key for a given decay time.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return int
     */
    public function hit($key, $decaySeconds = 60, $slidingWindow = false)
    {
        if ($slidingWindow) {
            return $this->slidingWindowHit($key, $decaySeconds);
        }

        return $this->increment($key, $decaySeconds);
    }

    /**
     * Increment the counter for a given key for a given decay time by a given amount.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @param  int  $amount
     * @return int
     */
    public function increment($key, $decaySeconds = 60, $amount = 1)
    {
        $key = $this->cleanRateLimiterKey($key);

        $this->cache->add(
            $key.':timer', $this->availableAt($decaySeconds), $decaySeconds
        );

        $added = $this->withoutSerializationOrCompression(
            fn () => $this->cache->add($key, 0, $decaySeconds)
        );

        $hits = (int) $this->cache->increment($key, $amount);

        if (! $added && $hits == 1) {
            $this->withoutSerializationOrCompression(
                fn () => $this->cache->put($key, 1, $decaySeconds)
            );
        }

        return $hits;
    }

    /**
     * Decrement the counter for a given key for a given decay time by a given amount.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @param  int  $amount
     * @return int
     */
    public function decrement($key, $decaySeconds = 60, $amount = 1)
    {
        return $this->increment($key, $decaySeconds, $amount * -1);
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function attempts($key)
    {
        $key = $this->cleanRateLimiterKey($key);

        return $this->withoutSerializationOrCompression(fn () => $this->cache->get($key, 0));
    }

    /**
     * Reset the number of attempts for the given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function resetAttempts($key)
    {
        $key = $this->cleanRateLimiterKey($key);

        return $this->cache->forget($key);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return int
     */
    public function remaining($key, $maxAttempts, $decaySeconds = 60, $slidingWindow = false)
    {
        if ($slidingWindow) {
            return $this->slidingWindowRemaining($key, $maxAttempts, $decaySeconds);
        }

        $key = $this->cleanRateLimiterKey($key);

        $attempts = $this->attempts($key);

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    public function retriesLeft($key, $maxAttempts)
    {
        return $this->remaining($key, $maxAttempts);
    }

    /**
     * Increment the counter for a given key using the sliding window algorithm.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @param  int  $amount
     * @return int
     */
    protected function slidingWindowHit($key, $decaySeconds = 60, $amount = 1)
    {
        $key = $this->cleanRateLimiterKey($key);
        $decaySeconds = $this->secondsUntil($decaySeconds);

        $windowStart = $this->cache->get($key.':sw:timer');
        $now = $this->currentTime();

        if (is_null($windowStart) || $now >= ($windowStart + $decaySeconds)) {
            $previousCount = is_null($windowStart) ? 0 : (int) $this->withoutSerializationOrCompression(
                fn () => $this->cache->get($key.':sw:current', 0)
            );

            $this->withoutSerializationOrCompression(
                fn () => $this->cache->put($key.':sw:previous', $previousCount, $decaySeconds * 2)
            );
            $this->cache->put($key.':sw:timer', $now, $decaySeconds * 2);

            $this->withoutSerializationOrCompression(
                fn () => $this->cache->put($key.':sw:current', 0, $decaySeconds * 2)
            );
        }

        return (int) $this->withoutSerializationOrCompression(
            fn () => $this->cache->increment($key.':sw:current', $amount)
        );
    }

    /**
     * Determine if the given key has been "accessed" too many times using the sliding window algorithm.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @return bool
     */
    protected function slidingWindowTooManyAttempts($key, $maxAttempts, $decaySeconds = 60)
    {
        return $this->slidingWindowEffectiveAttempts($key, $decaySeconds) >= $maxAttempts;
    }

    /**
     * Get the number of retries left for the given key using the sliding window algorithm.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @return int
     */
    protected function slidingWindowRemaining($key, $maxAttempts, $decaySeconds = 60)
    {
        return max(0, $maxAttempts - $this->slidingWindowEffectiveAttempts($key, $decaySeconds));
    }

    /**
     * Get the number of seconds until the sliding window resets for the given key.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @return int
     */
    protected function slidingWindowAvailableIn($key, $decaySeconds = 60)
    {
        $key = $this->cleanRateLimiterKey($key);
        $decaySeconds = $this->secondsUntil($decaySeconds);

        $windowStart = $this->cache->get($key.':sw:timer');

        if (is_null($windowStart)) {
            return 0;
        }

        return max(0, ($windowStart + $decaySeconds) - $this->currentTime());
    }

    /**
     * Get the effective number of attempts for the given key using the sliding window algorithm.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @return int
     */
    protected function slidingWindowEffectiveAttempts($key, $decaySeconds = 60)
    {
        $key = $this->cleanRateLimiterKey($key);
        $decaySeconds = $this->secondsUntil($decaySeconds);

        $windowStart = $this->cache->get($key.':sw:timer');

        if (is_null($windowStart)) {
            return 0;
        }

        $now = $this->currentTime();

        if ($now >= $windowStart + ($decaySeconds * 2)) {
            return 0;
        }

        $current = (int) $this->withoutSerializationOrCompression(
            fn () => $this->cache->get($key.':sw:current', 0)
        );

        $previous = (int) $this->withoutSerializationOrCompression(
            fn () => $this->cache->get($key.':sw:previous', 0)
        );

        if ($now >= $windowStart + $decaySeconds) {
            $elapsed = $now - ($windowStart + $decaySeconds);
            $overlapRatio = max(0, 1 - ($elapsed / $decaySeconds));

            return (int) floor($overlapRatio * $current);
        }

        $elapsed = $now - $windowStart;
        $overlapRatio = max(0, 1 - ($elapsed / $decaySeconds));

        return (int) floor($overlapRatio * $previous) + $current;
    }

    /**
     * Clear the hits and lockout timer for the given key.
     *
     * @param  string  $key
     * @return void
     */
    public function clear($key)
    {
        $key = $this->cleanRateLimiterKey($key);

        $this->resetAttempts($key);

        $this->cache->forget($key.':timer');
        $this->cache->forget($key.':sw:current');
        $this->cache->forget($key.':sw:previous');
        $this->cache->forget($key.':sw:timer');
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|int  $decaySeconds
     * @param  bool  $slidingWindow
     * @return int
     */
    public function availableIn($key, $decaySeconds = 60, $slidingWindow = false)
    {
        if ($slidingWindow) {
            return $this->slidingWindowAvailableIn($key, $decaySeconds);
        }

        $key = $this->cleanRateLimiterKey($key);

        return max(0, $this->cache->get($key.':timer') - $this->currentTime());
    }

    /**
     * Clean the rate limiter key from unicode characters.
     *
     * @param  string  $key
     * @return string
     */
    public function cleanRateLimiterKey($key)
    {
        return preg_replace('/&([a-z])[a-z]+;/i', '$1', htmlentities($key));
    }

    /**
     * Execute the given callback without serialization or compression when applicable.
     *
     * @param  callable  $callback
     * @return mixed
     */
    protected function withoutSerializationOrCompression(callable $callback)
    {
        $store = $this->cache->getStore();

        if (! $store instanceof RedisStore) {
            return $callback();
        }

        $connection = $store->connection();

        if (! $connection instanceof PhpRedisConnection) {
            return $callback();
        }

        return $connection->withoutSerializationOrCompression($callback);
    }

    /**
     * Resolve the rate limiter name.
     *
     * @param  \UnitEnum|string  $name
     * @return string
     */
    private function resolveLimiterName($name): string
    {
        return (string) enum_value($name);
    }
}
