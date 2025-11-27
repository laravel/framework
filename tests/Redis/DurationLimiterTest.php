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

        (new DurationLimiter($this->redis(), 'key', 2, 2))->block(0, function () use (&$store) {
            $store[] = 1;
        });

        (new DurationLimiter($this->redis(), 'key', 2, 2))->block(0, function () use (&$store) {
            $store[] = 2;
        });

        try {
            (new DurationLimiter($this->redis(), 'key', 2, 2))->block(0, function () use (&$store) {
                $store[] = 3;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $this->assertEquals([1, 2], $store);

        sleep(2);

        (new DurationLimiter($this->redis(), 'key', 2, 2))->block(0, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 2, 3], $store);
    }

    public function testItFailsImmediatelyOrRetriesForAWhileBasedOnAGivenTimeout()
    {
        $store = [];

        (new DurationLimiter($this->redis(), 'key', 1, 1))->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            (new DurationLimiter($this->redis(), 'key', 1, 1))->block(0, function () use (&$store) {
                $store[] = 2;
            });
        } catch (Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        (new DurationLimiter($this->redis(), 'key', 1, 1))->block(2, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    public function testItReturnsTheCallbackResult()
    {
        $limiter = new DurationLimiter($this->redis(), 'key', 1, 1);

        $result = $limiter->block(1, function () {
            return 'foo';
        });

        $this->assertSame('foo', $result);
    }

    public function testAcquireSetsDecaysAtAndRemaining()
    {
        $limiter = new DurationLimiter($this->redis(), 'acquire-key', 2, 2);

        $acquired1 = $limiter->acquire();
        $this->assertTrue($acquired1);
        $this->assertGreaterThanOrEqual(time(), $limiter->decaysAt);
        $this->assertSame(1, $limiter->remaining);

        $acquired2 = $limiter->acquire();
        $this->assertTrue($acquired2);
        $this->assertSame(0, $limiter->remaining);

        $acquired3 = $limiter->acquire();
        $this->assertFalse($acquired3);
        $this->assertSame(0, $limiter->remaining);
    }

    public function testTooManyAttemptsReportsCorrectly()
    {
        $limiter = new DurationLimiter($this->redis(), 'too-many-key', 2, 1);

        // Initially, should not have too many attempts
        $this->assertFalse($limiter->tooManyAttempts());
        $this->assertSame(0, $limiter->decaysAt); // As per script for non-existing key
        $this->assertGreaterThan(0, $limiter->remaining); // Remaining is positive future timestamp placeholder

        // Use up the available slots
        $this->assertTrue($limiter->acquire());
        $this->assertTrue($limiter->acquire());

        // Now, too many attempts within the same window
        $this->assertTrue($limiter->tooManyAttempts());
        $this->assertSame(0, max(0, $limiter->remaining));

        // After decay window, attempts should be allowed again
        sleep(1);
        $this->assertFalse($limiter->tooManyAttempts());
    }

    public function testClearResetsLimiter()
    {
        $limiter = new DurationLimiter($this->redis(), 'clear-key', 1, 2);

        $this->assertTrue($limiter->acquire());
        $this->assertFalse($limiter->acquire());

        // Clear and try again
        $limiter->clear();
        $this->assertTrue($limiter->acquire());
    }

    public function testBlockReturnsTrueWithoutCallback()
    {
        $limiter = new DurationLimiter($this->redis(), 'no-callback-key', 1, 1);

        $this->assertTrue($limiter->block(1));
    }

    public function testAcquireResetsAfterDecay()
    {
        $limiter = new DurationLimiter($this->redis(), 'reset-after-decay-key', 1, 1);

        $this->assertTrue($limiter->acquire());
        $this->assertFalse($limiter->acquire());

        sleep(1);

        $this->assertTrue($limiter->acquire());
        $this->assertSame(0, $limiter->remaining);
    }

    private function redis()
    {
        return $this->redis['phpredis']->connection();
    }
}
