<?php

declare(strict_types=1);

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Mockery;
use PHPUnit\Framework\TestCase;

class RateLimiterRedisTtlFixTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRestoresRedisTtlWhenTtlIsMinusOne(): void
    {
        $key = 'test-key';
        $decay = 60;
        $amount = 1;

        $redisConnection = Mockery::mock('stdClass');
        $redisConnection->shouldReceive('ttl')
            ->once()
            ->with($key)
            ->andReturn(-1);
        $redisConnection->shouldReceive('expire')
            ->once()
            ->with($key, $decay)
            ->andReturn(true);

        $store = Mockery::mock(RedisStore::class);

        $store->shouldReceive('add')
            ->once()
            ->with($key . ':timer', Mockery::type('int'), $decay)
            ->andReturn(true);
        $store->shouldReceive('add')
            ->once()
            ->with($key, 0, $decay)
            ->andReturn(true);
        $store->shouldReceive('increment')
            ->once()
            ->with($key, $amount)
            ->andReturn($amount);
        $store->shouldReceive('put')->never();

        // ✅ allow multiple calls
        $store->shouldReceive('connection')
            ->atLeast()->once()
            ->andReturn($redisConnection);

        $limiter = new RateLimiter(new Repository($store));

        $hits = $limiter->increment($key, $decay, $amount);

        $this->assertSame(1, $hits);
    }

    public function testDoesNotCallExpireWhenTtlIsNonNegative(): void
    {
        $key = 'test-key';
        $decay = 60;

        $redisConnection = Mockery::mock('stdClass');
        $redisConnection->shouldReceive('ttl')
            ->once()
            ->with($key)
            ->andReturn(30);
        $redisConnection->shouldReceive('expire')->never();

        $store = Mockery::mock(RedisStore::class);

        $store->shouldReceive('add')
            ->once()
            ->with($key . ':timer', Mockery::type('int'), $decay)
            ->andReturn(true);
        $store->shouldReceive('add')
            ->once()
            ->with($key, 0, $decay)
            ->andReturn(true);
        $store->shouldReceive('increment')
            ->once()
            ->with($key, 1)
            ->andReturn(1);
        $store->shouldReceive('put')->never();

        // ✅ allow multiple calls
        $store->shouldReceive('connection')
            ->atLeast()->once()
            ->andReturn($redisConnection);

        $limiter = new RateLimiter(new Repository($store));

        $hits = $limiter->increment($key, $decay, 1);

        $this->assertSame(1, $hits);
    }

    public function testHandlesEdgeCaseWhenKeyExistedButHitsBecomesOneAgain(): void
    {
        $key = 'test-key';
        $decay = 60;

        $redisConnection = Mockery::mock('stdClass');
        $redisConnection->shouldReceive('ttl')
            ->once()
            ->with($key)
            ->andReturn(-1);
        $redisConnection->shouldReceive('expire')
            ->once()
            ->with($key, $decay)
            ->andReturn(true);

        $store = Mockery::mock(RedisStore::class);

        $store->shouldReceive('add')
            ->once()
            ->with($key . ':timer', Mockery::type('int'), $decay)
            ->andReturn(true);
        $store->shouldReceive('add')
            ->once()
            ->with($key, 0, $decay)
            ->andReturn(false);
        $store->shouldReceive('increment')
            ->once()
            ->with($key, 1)
            ->andReturn(1);
        $store->shouldReceive('put')
            ->once()
            ->with($key, 1, $decay)
            ->andReturn(true);

        // ✅ allow multiple calls
        $store->shouldReceive('connection')
            ->atLeast()->once()
            ->andReturn($redisConnection);

        $limiter = new RateLimiter(new Repository($store));

        $hits = $limiter->increment($key, $decay, 1);

        $this->assertSame(1, $hits);
    }

    public function testRemainsNoopForNonRedisStoreAndStillReturnsHits(): void
    {
        $store = new ArrayStore;
        $limiter = new RateLimiter(new Repository($store));

        $key = 'test-key';
        $hits = $limiter->increment($key, 2, 1);

        $this->assertSame(1, $hits);
        $this->assertFalse($limiter->tooManyAttempts($key, 2));
    }
}
