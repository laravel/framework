<?php

namespace Illuminate\Tests\Redis;

use PHPUnit\Framework\TestCase;
use Illuminate\Redis\Limiters\ConcurrencyLimiter;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

/**
 * @group redislimiters
 */
class ConcurrentLimiterTest extends TestCase
{
    use InteractsWithRedis;

    public function setup()
    {
        parent::setup();

        $this->setUpRedis();
    }

    /**
     * @test
     */
    public function it_locks_tasks_when_no_slot_available()
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
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        (new ConcurrencyLimiterMockThatDoesntRelease($this->redis(), 'other_key', 2, 5))->block(2, function () use (&$store) {
            $store[] = 4;
        });

        $this->assertEquals([1, 2, 4], $store);
    }

    /**
     * @test
     */
    public function it_releases_lock_after_task_finishes()
    {
        $store = [];

        foreach (range(1, 4) as $i) {
            (new ConcurrencyLimiter($this->redis(), 'key', 2, 5))->block(2, function () use (&$store, $i) {
                $store[] = $i;
            });
        }

        $this->assertEquals([1, 2, 3, 4], $store);
    }

    /**
     * @test
     */
    public function it_releases_lock_if_task_took_too_long()
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
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        usleep(1.2 * 1000000);

        $lock->block(0, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    /**
     * @test
     */
    public function it_fails_immediately_or_retries_for_a_while_based_on_a_given_timeout()
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
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $lock->block(3, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    /**
     * @test
     */
    public function it_fails_after_retry_timeout()
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
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $this->assertEquals([1], $store);
    }

    private function redis()
    {
        return $this->redis['predis']->connection();
    }
}

class ConcurrencyLimiterMockThatDoesntRelease extends ConcurrencyLimiter
{
    protected function release($Key)
    {
        //
    }
}
