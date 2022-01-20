<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Limiters\DurationLimiter;
use PHPUnit\Framework\TestCase;
use Throwable;

class DurationLimiterTest extends TestCase
{
    use InteractsWithRedis;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testItLocksTasksWhenNoSlotAvailable($connection)
    {
        $store = [];

        (new DurationLimiter($this->redis($connection), 'key', 2, 2))->block(0, function () use (&$store) {
            $store[] = 1;
        });

        (new DurationLimiter($this->redis($connection), 'key', 2, 2))->block(0, function () use (&$store) {
            $store[] = 2;
        });

        try {
            (new DurationLimiter($this->redis($connection), 'key', 2, 2))->block(0, function () use (&$store) {
                $store[] = 3;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $this->assertEquals([1, 2], $store);

        sleep(2);

        (new DurationLimiter($this->redis($connection), 'key', 2, 2))->block(0, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 2, 3], $store);
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testItFailsImmediatelyOrRetriesForAWhileBasedOnAGivenTimeout($connection)
    {
        $store = [];

        (new DurationLimiter($this->redis($connection), 'key', 1, 1))->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            (new DurationLimiter($this->redis($connection), 'key', 1, 1))->block(0, function () use (&$store) {
                $store[] = 2;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        (new DurationLimiter($this->redis($connection), 'key', 1, 1))->block(2, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testItReturnsTheCallbackResult($connection)
    {
        $limiter = new DurationLimiter($this->redis($connection), 'key', 1, 1);

        $result = $limiter->block(1, function () {
            return 'foo';
        });

        $this->assertSame('foo', $result);
    }

    private function redis($connection)
    {
        return $this->getRedisManager($connection)->connection();
    }
}
