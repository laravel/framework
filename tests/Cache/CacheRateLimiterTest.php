<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CacheRateLimiterTest extends TestCase
{
    protected Repository $cache;

    protected RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        $this->cache = new Repository(new ArrayStore);
        $this->rateLimiter = new RateLimiter($this->cache);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
    }

    public function testTooManyAttemptsReturnTrueIfAlreadyLockedOut()
    {
        $this->cache->put('key', 1, 60);
        $this->cache->put('key:timer', time() + 60, 60);

        $this->assertTrue($this->rateLimiter->tooManyAttempts('key', 1));
    }

    public function testHitProperlyIncrementsAttemptCount()
    {
        $this->rateLimiter->hit('key', 1);

        $this->assertSame(1, $this->rateLimiter->attempts('key'));
        $this->assertTrue($this->cache->has('key:timer'));
    }

    public function testIncrementProperlyIncrementsAttemptCount()
    {
        $this->rateLimiter->increment('key', 1, 5);

        $this->assertSame(5, $this->rateLimiter->attempts('key'));
    }

    public function testDecrementProperlyDecrementsAttemptCount()
    {
        $this->rateLimiter->decrement('key', 1, 5);

        $this->assertSame(-5, $this->rateLimiter->attempts('key'));
    }

    public function testHitHasNoMemoryLeak()
    {
        Carbon::setTestNow(Carbon::now());
        $this->cache->forever('key', 0);

        $this->rateLimiter->hit('key', 1);

        $this->assertSame(1, $this->rateLimiter->attempts('key'));

        Carbon::setTestNow(Carbon::now()->addSeconds(2));

        $this->assertSame(0, $this->rateLimiter->attempts('key'));
    }

    public function testIncrementWithCustomAmountHasNoMemoryLeak()
    {
        Carbon::setTestNow(Carbon::now());
        $this->cache->forever('key', 0);

        $this->rateLimiter->increment('key', 60, 2);

        $this->assertSame(2, $this->rateLimiter->attempts('key'));

        Carbon::setTestNow(Carbon::now()->addSeconds(61));

        $this->assertSame(0, $this->rateLimiter->attempts('key'));
    }

    public function testRemainingIsNotNegative(): void
    {
        $this->cache->put('key', 5, 60);

        $this->assertSame(0, $this->rateLimiter->remaining('key', 3));
        $this->assertSame(0, $this->rateLimiter->retriesLeft('key', 3));
    }

    public function testRetriesLeftReturnsCorrectCount()
    {
        $this->cache->put('key', 3, 60);

        $this->assertEquals(2, $this->rateLimiter->retriesLeft('key', 5));
    }

    public function testClearClearsTheCacheKeys()
    {
        $this->rateLimiter->hit('key', 60);

        $this->rateLimiter->clear('key');

        $this->assertFalse($this->cache->has('key'));
        $this->assertFalse($this->cache->has('key:timer'));
    }

    public function testAvailableInReturnsPositiveValues()
    {
        $this->cache->put('key:timer:timer', Carbon::now()->subMinute()->getTimestamp(), 60);

        $this->assertSame(0, $this->rateLimiter->availableIn('key:timer'));
        $this->assertSame(0, $this->rateLimiter->availableIn('missing:timer'));
    }

    public function testAttemptsCallbackReturnsTrue()
    {
        $executed = false;

        $this->rateLimiter->attempt('key', 1, function () use (&$executed) {
            $executed = true;
        }, 1);

        $this->assertTrue($executed);
        $this->assertSame(1, $this->rateLimiter->attempts('key'));
    }

    public function testAttemptsCallbackReturnsCallbackReturn()
    {
        $this->assertSame('foo', $this->rateLimiter->attempt('key', 6, function () {
            return 'foo';
        }, 1));

        $this->assertFalse($this->rateLimiter->attempt('key', 6, function () {
            return false;
        }, 1));

        $this->assertSame([], $this->rateLimiter->attempt('key', 6, function () {
            return [];
        }, 1));

        $this->assertSame(0, $this->rateLimiter->attempt('key', 6, function () {
            return 0;
        }, 1));

        $this->assertSame(0.0, $this->rateLimiter->attempt('key', 6, function () {
            return 0.0;
        }, 1));

        $this->assertSame('', $this->rateLimiter->attempt('key', 6, function () {
            return '';
        }, 1));

        $this->assertSame(6, $this->rateLimiter->attempts('key'));
    }

    public function testAttemptsCallbackReturnsFalse()
    {
        $this->cache->put('key', 2, 60);
        $this->cache->put('key:timer', time() + 60, 60);

        $executed = false;

        $this->assertFalse($this->rateLimiter->attempt('key', 1, function () use (&$executed) {
            $executed = true;
        }, 1));
        $this->assertFalse($executed);
    }

    public function testKeysAreSanitizedFromUnicodeCharacters()
    {
        $this->cache->put('john', 1, 60);
        $this->cache->put('john:timer', time() + 60, 60);

        $this->assertTrue($this->rateLimiter->tooManyAttempts('jôhn', 1));
    }

    public function testKeyIsSanitizedOnlyOnce()
    {
        $key = "john'doe";
        $cleanedKey = $this->rateLimiter->cleanRateLimiterKey($key);

        $this->cache->put($cleanedKey, 1, 60);
        $this->cache->put("$cleanedKey:timer", time() + 60, 60);

        $this->assertTrue($this->rateLimiter->tooManyAttempts($key, 1));
    }
}
