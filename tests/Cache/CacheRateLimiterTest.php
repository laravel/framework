<?php

namespace Illuminate\Tests\Cache;

use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheRateLimiterTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testTooManyAttemptsReturnTrueIfAlreadyLockedOut()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('key', 0)->andReturn(1);
        $cache->shouldReceive('has')->once()->with('key:timer')->andReturn(true);
        $cache->shouldReceive('add')->never();
        $rateLimiter = new RateLimiter($cache);

        $this->assertTrue($rateLimiter->tooManyAttempts('key', 1));
    }

    public function testHitProperlyIncrementsAttemptCount()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('add')->once()->with('key:timer', m::type('int'), 1)->andReturn(true);
        $cache->shouldReceive('add')->once()->with('key', 0, 1)->andReturn(true);
        $cache->shouldReceive('increment')->once()->with('key')->andReturn(1);
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->hit('key', 1);
    }

    public function testHitHasNoMemoryLeak()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('add')->once()->with('key:timer', m::type('int'), 1)->andReturn(true);
        $cache->shouldReceive('add')->once()->with('key', 0, 1)->andReturn(false);
        $cache->shouldReceive('increment')->once()->with('key')->andReturn(1);
        $cache->shouldReceive('put')->once()->with('key', 1, 1);
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->hit('key', 1);
    }

    public function testRetriesLeftReturnsCorrectCount()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('key', 0)->andReturn(3);
        $rateLimiter = new RateLimiter($cache);

        $this->assertEquals(2, $rateLimiter->retriesLeft('key', 5));
    }

    public function testClearClearsTheCacheKeys()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('forget')->once()->with('key');
        $cache->shouldReceive('forget')->once()->with('key:timer');
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->clear('key');
    }

    public function testAvailableInReturnsPositiveValues()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->andReturn(now()->subSeconds(60)->getTimestamp(), null);
        $rateLimiter = new RateLimiter($cache);

        $this->assertTrue($rateLimiter->availableIn('key:timer') >= 0);
        $this->assertTrue($rateLimiter->availableIn('key:timer') >= 0);
    }

    public function testAttemptsCallbackReturnsTrue()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('key', 0)->andReturn(0);
        $cache->shouldReceive('add')->once()->with('key:timer', m::type('int'), 1);
        $cache->shouldReceive('add')->once()->with('key', 0, 1)->andReturns(1);
        $cache->shouldReceive('increment')->once()->with('key')->andReturn(1);

        $executed = false;

        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->attempt('key', 1, function () use (&$executed) {
            $executed = true;
        }, 1);
        $this->assertTrue($executed);
    }

    public function testAttemptsCallbackReturnsCallbackReturn()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->times(6)->with('key', 0)->andReturn(0);
        $cache->shouldReceive('add')->times(6)->with('key:timer', m::type('int'), 1);
        $cache->shouldReceive('add')->times(6)->with('key', 0, 1)->andReturns(1);
        $cache->shouldReceive('increment')->times(6)->with('key')->andReturn(1);

        $rateLimiter = new RateLimiter($cache);

        $this->assertSame('foo', $rateLimiter->attempt('key', 1, function () {
            return 'foo';
        }, 1));

        $this->assertSame(false, $rateLimiter->attempt('key', 1, function () {
            return false;
        }, 1));

        $this->assertSame([], $rateLimiter->attempt('key', 1, function () {
            return [];
        }, 1));

        $this->assertSame(0, $rateLimiter->attempt('key', 1, function () {
            return 0;
        }, 1));

        $this->assertSame(0.0, $rateLimiter->attempt('key', 1, function () {
            return 0.0;
        }, 1));

        $this->assertSame('', $rateLimiter->attempt('key', 1, function () {
            return '';
        }, 1));
    }

    public function testAttemptsCallbackReturnsFalse()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('key', 0)->andReturn(2);
        $cache->shouldReceive('has')->once()->with('key:timer')->andReturn(true);

        $executed = false;

        $rateLimiter = new RateLimiter($cache);

        $this->assertFalse($rateLimiter->attempt('key', 1, function () use (&$executed) {
            $executed = true;
        }, 1));
        $this->assertFalse($executed);
    }

    public function testKeysAreSanitizedFromUnicodeCharacters()
    {
        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('john', 0)->andReturn(1);
        $cache->shouldReceive('has')->once()->with('john:timer')->andReturn(true);
        $cache->shouldReceive('add')->never();
        $rateLimiter = new RateLimiter($cache);

        $this->assertTrue($rateLimiter->tooManyAttempts('jôhn', 1));
    }

    public function testKeyIsSanitizedOnlyOnce()
    {
        $cache = m::mock(Cache::class);
        $rateLimiter = new RateLimiter($cache);

        $key = "john'doe";
        $cleanedKey = $rateLimiter->cleanRateLimiterKey($key);

        $cache->shouldReceive('get')->once()->with($cleanedKey, 0)->andReturn(1);
        $cache->shouldReceive('has')->once()->with("$cleanedKey:timer")->andReturn(true);
        $cache->shouldReceive('add')->never();

        $this->assertTrue($rateLimiter->tooManyAttempts($key, 1));
    }

    public function testAbortIfExhaustedExpectCorrectHeaders()
    {
        Carbon::setTestNow(Carbon::now());

        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->twice()->with('key', 0)->andReturn(1);
        $cache->shouldReceive('has')->once()->with('key:timer')->andReturn(true);
        $cache->shouldReceive('get')->once()->with('key:timer')->andReturn($reset = Carbon::now()->addSeconds(60)->getTimestamp());
        $cache->shouldReceive('add')->never();
        $rateLimiter = new RateLimiter($cache);

        try {
            $rateLimiter->abortIfExhausted('key', 1);
        } catch (ThrottleRequestsException $e) {
            $this->assertSame('Too many attempts.', $e->getMessage());
            $this->assertSame(1, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertSame(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertSame(60, $e->getHeaders()['Retry-After']);
            $this->assertSame($reset, $e->getHeaders()['X-RateLimit-Reset']);
        }
    }

    public function testAbortIfExhaustedWhenRemaining()
    {
        Carbon::setTestNow(Carbon::now());

        $cache = m::mock(Cache::class);
        $cache->shouldReceive('get')->once()->with('key', 0)->andReturn(1);
        $cache->shouldReceive('add')->once()->with('key', 0, 60);
        $cache->shouldReceive('add')->once()->with('key:timer', now()->addMinute()->getTimestamp(), 60);
        $cache->shouldReceive('increment')->once()->with('key')->andReturn(2);
        $rateLimiter = new RateLimiter($cache);

        $rateLimiter->abortIfExhausted('key', 2);
    }
}
