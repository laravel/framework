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

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
    }

    public function testItLocksTasksWhenNoSlotAvailable()
    {
        $store = [];

        foreach (range(1, 2) as $i) {
            (new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'key', 2, 5))->block(2, function () use (&$store, $i) {
                $store[] = $i;
            });
        }

        try {
            (new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'key', 2, 5))->block(0, function () use (&$store) {
                $store[] = 3;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        (new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'other_key', 2, 5))->block(2, function () use (&$store) {
            $store[] = 4;
        });

        $this->assertEquals([1, 2, 4], $store);
    }

    public function testItReleasesLockAfterTaskFinishes()
    {
        $store = [];

        foreach (range(1, 4) as $i) {
            (new ConcurrencyLimiter($this->redis(), 'key', 2, 5))->block(2, function () use (&$store, $i) {
                $store[] = $i;
            });
        }

        $this->assertEquals([1, 2, 3, 4], $store);
    }

    public function testItReleasesLockIfTaskTookTooLong()
    {
        $store = [];

        $lock = (new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'key', 1, 1));

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

        usleep(1.2 * 1000000);

        $lock->block(0, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    public function testItFailsImmediatelyOrRetriesForAWhileBasedOnAGivenTimeout()
    {
        $store = [];

        $lock = (new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'key', 1, 2));

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

    public function testItFailsAfterRetryTimeout()
    {
        $store = [];

        $lock = (new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'key', 1, 10));

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

    public function testItReleasesIfErrorIsThrown()
    {
        $store = [];

        $lock = new ConcurrencyLimiter($this->redis(), 'key', 1, 5);

        try {
            $lock->block(1, function () {
                throw new Error;
            });
        } catch (Error) {
        }

        $lock = new ConcurrencyLimiter($this->redis(), 'key', 1, 5);
        $lock->block(1, function () use (&$store) {
            $store[] = 1;
        });

        $this->assertEquals([1], $store);
    }

    public function testTryAcquireReturnsImmediatelyWhenNoSlotAvailable()
    {
        $store = [];

        $lock = new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'key', 1, 5);

        $result = $lock->tryAcquire(function () use (&$store) {
            $store[] = 1;
        });

        $this->assertTrue($result);
        $this->assertEquals([1], $store);

        $result = $lock->tryAcquire(function () use (&$store) {
            $store[] = 2;
        });

        $this->assertFalse($result);
        $this->assertEquals([1], $store);
    }

    public function testTryAcquireReleasesLockAfterCallback()
    {
        $lock = new ConcurrencyLimiter($this->redis(), 'key', 1, 5);

        $result = $lock->tryAcquire(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);

        $result = $lock->tryAcquire(function () {
            return 'also success';
        });

        $this->assertEquals('also success', $result);
    }

    public function testCurrentLocksReturnsNumberOfOccupiedSlots()
    {
        $lock = new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'key', 3, 5);

        $this->assertEquals(0, $lock->currentLocks());
        $this->assertEquals(3, $lock->available());

        $lock->tryAcquire();

        $this->assertEquals(1, $lock->currentLocks());
        $this->assertEquals(2, $lock->available());

        $lock->tryAcquire();

        $this->assertEquals(2, $lock->currentLocks());
        $this->assertEquals(1, $lock->available());

        $lock->tryAcquire();

        $this->assertEquals(3, $lock->currentLocks());
        $this->assertEquals(0, $lock->available());
    }

    private function redis()
    {
        return $this->redis['phpredis']->connection();
    }
}

class ConcurrencyLimiterMockThatDoesntRelease extends ConcurrencyLimiter
{
    protected function release($key, $id)
    {
        //
    }
}
