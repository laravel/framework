<?php

namespace Illuminate\Cache;

use BadMethodCallException;
use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\InteractsWithTime;
use InvalidArgumentException;

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
     * Register a named limiter configuration.
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
     * @return mixed
     */
    public function attempt($key, $maxAttempts, Closure $callback, $decaySeconds = 60)
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        if (is_null($result = $callback())) {
            $result = true;
        }

        return tap($result, function () use ($key, $decaySeconds) {
            $this->hit($key, $decaySeconds);
        });
    }

    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return bool
     */
    public function tooManyAttempts($key, $maxAttempts)
    {
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
     * @return int
     */
    public function hit($key, $decaySeconds = 60)
    {
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
     * @return int
     */
    public function remaining($key, $maxAttempts)
    {
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
     * Attempt to consume tokens from a token bucket.
     *
     * @param  string  $key
     * @param  int|float  $capacity
     * @param  int|float  $refillPerSecond
     * @param  int|float  $cost
     * @param  bool  $consume
     * @return array{allowed: bool, remaining: float, retryAfter: int}
     */
    public function tokenBucket($key, int|float $capacity, int|float $refillPerSecond = 1, int|float $cost = 1, bool $consume = true): array
    {
        $capacity = (float) $capacity;
        $refillPerSecond = (float) $refillPerSecond;
        $cost = (float) $cost;

        if ($capacity <= 0) {
            throw new InvalidArgumentException('Token bucket capacity must be greater than zero.');
        }

        if ($refillPerSecond <= 0) {
            throw new InvalidArgumentException('Token bucket refill rate must be greater than zero.');
        }

        if ($cost <= 0) {
            throw new InvalidArgumentException('Token bucket cost must be greater than zero.');
        }

        $key = $this->cleanRateLimiterKey($key);
        $stateKey = "{$key}:bucket";
        $now = $this->currentTimeInMicroseconds();

        return $this->withTokenBucketLock($key, function () use ($key, $stateKey, $capacity, $refillPerSecond, $cost, $consume, $now) {
            $state = $this->cache->get($stateKey, [
                'tokens' => $capacity,
                'timestamp' => $now,
            ]);

            $tokens = (float) ($state['tokens'] ?? $capacity);
            $timestamp = (float) ($state['timestamp'] ?? $now);
            $elapsedSeconds = max(0, $now - $timestamp);

            $tokens = min($capacity, $tokens + ($elapsedSeconds * $refillPerSecond));

            $allowed = $tokens >= $cost;

            if ($allowed && $consume) {
                $tokens -= $cost;
            }

            $retryAfter = $allowed
                ? 0
                : (int) ceil(($cost - $tokens) / $refillPerSecond);

            $ttl = max(1, (int) ceil(($capacity / $refillPerSecond) * 2));

            $this->cache->put($stateKey, [
                'tokens' => $tokens,
                'timestamp' => $now,
            ], $ttl);

            if ($retryAfter > 0) {
                $this->cache->put("{$key}:timer", $this->availableAt($retryAfter), $retryAfter);
            } else {
                $this->cache->forget("{$key}:timer");
            }

            return [
                'allowed' => $allowed,
                'remaining' => max(0, $tokens),
                'retryAfter' => max(0, $retryAfter),
            ];
        });
    }

    /**
     * Get the number of whole tokens remaining in a token bucket.
     *
     * @param  string  $key
     * @param  int|float  $capacity
     * @param  int|float  $refillPerSecond
     * @return int
     */
    public function remainingTokens($key, int|float $capacity, int|float $refillPerSecond = 1): int
    {
        return (int) floor(
            $this->tokenBucket($key, $capacity, $refillPerSecond, consume: false)['remaining']
        );
    }

    /**
     * Get the number of seconds until the requested token cost is available.
     *
     * @param  string  $key
     * @param  int|float  $capacity
     * @param  int|float  $refillPerSecond
     * @param  int|float  $cost
     * @return int
     */
    public function availableInTokenBucket($key, int|float $capacity, int|float $refillPerSecond = 1, int|float $cost = 1): int
    {
        return $this->tokenBucket($key, $capacity, $refillPerSecond, $cost, consume: false)['retryAfter'];
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
        $this->cache->forget($key.':bucket');
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param  string  $key
     * @return int
     */
    public function availableIn($key)
    {
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
     * Execute a callback while holding a token bucket lock when supported.
     *
     * @template TReturn
     *
     * @param  string  $key
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function withTokenBucketLock(string $key, callable $callback)
    {
        try {
            return $this->cache->withoutOverlapping(
                "illuminate:cache:rate_limiter:bucket_lock:{$key}",
                $callback,
                lockFor: 5,
                waitFor: 1
            );
        } catch (BadMethodCallException) {
            return $callback();
        }
    }

    /**
     * Get the current testable timestamp in seconds (with microsecond precision).
     *
     * @return float
     */
    protected function currentTimeInMicroseconds()
    {
        return Carbon::now()->getPreciseTimestamp(6) / 1_000_000;
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
