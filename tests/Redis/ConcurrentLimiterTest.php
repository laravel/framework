<?php

namespace Illuminate\Tests\Redis;

use Error;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Limiters\ConcurrencyLimiter;
use PHPUnit\Framework\TestCase;
use Throwable;

class ConcurrentLimiterTest extends TestCase
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

        foreach (range(1, 2) as $i) {
            (new ConcurrencyLimiterMockThatDoesntRelease($this->redis($connection), 'key', 2, 5))->block(2, function () use (&$store, $i) {
                $store[] = $i;
            });
        }

        try {
            (new ConcurrencyLimiterMockThatDoesntRelease($this->redis($connection), 'key', 2, 5))->block(0, function () use (&$store) {
                $store[] = 3;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        (new ConcurrencyLimiterMockThatDoesntRelease($this->redis($connection), 'other_key', 2, 5))->block(2, function () use (&$store) {
            $store[] = 4;
        });

        $this->assertEquals([1, 2, 4], $store);
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testItReleasesLockAfterTaskFinishes($connection)
    {
        $store = [];

        foreach (range(1, 4) as $i) {
            (new ConcurrencyLimiter($this->redis($connection), 'key', 2, 5))->block(2, function () use (&$store, $i) {
                $store[] = $i;
            });
        }

        $this->assertEquals([1, 2, 3, 4], $store);
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testItReleasesLockIfTaskTookTooLong($connection)
    {
        $store = [];

        $lock = (new ConcurrencyLimiterMockThatDoesntRelease($this->redis($connection), 'key', 1, 1));

        $lock->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            $lock->block(0, function () use (&$store) {
                $store[] = 2;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        usleep(1100000);

        $lock->block(0, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testItFailsImmediatelyOrRetriesForAWhileBasedOnAGivenTimeout($connection)
    {
        $store = [];

        $lock = (new ConcurrencyLimiterMockThatDoesntRelease($this->redis($connection), 'key', 1, 2));

        $lock->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            $lock->block(0, function () use (&$store) {
                $store[] = 2;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $lock->block(3, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testItFailsAfterRetryTimeout($connection)
    {
        $store = [];

        $lock = (new ConcurrencyLimiterMockThatDoesntRelease($this->redis($connection), 'key', 1, 10));

        $lock->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            $lock->block(2, function () use (&$store) {
                $store[] = 2;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $this->assertEquals([1], $store);
    }

    /**
     * @dataProvider redisConnectionDataProvider
     */
    public function testItReleasesIfErrorIsThrown($connection)
    {
        $store = [];

        $lock = new ConcurrencyLimiter($this->redis($connection), 'key', 1, 5);

        try {
            $lock->block(1, function () {
                throw new Error;
            });
        } catch (Error $e) {
        }

        $lock = new ConcurrencyLimiter($this->redis($connection), 'key', 1, 5);
        $lock->block(1, function () use (&$store) {
            $store[] = 1;
        });

        $this->assertEquals([1], $store);
    }

    private function redis($connection)
    {
        return $this->getRedisManager($connection)->connection();
    }
}

class ConcurrencyLimiterMockThatDoesntRelease extends ConcurrencyLimiter
{
    protected function release($key, $id)
    {
        //
    }
}
