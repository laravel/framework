<?php

namespace Illuminate\Tests\Redis;

use PHPUnit\Framework\TestCase;
use Illuminate\Redis\Limiters\DurationLimiter;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

/**
 * @group redislimiters
 */
class DurationLimiterTest extends TestCase
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
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        $this->assertEquals([1, 2], $store);

        sleep(2);

        (new DurationLimiter($this->redis(), 'key', 2, 2))->block(0, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 2, 3], $store);
    }

    /**
     * @test
     */
    public function it_fails_immediately_or_retries_for_a_while_based_on_a_given_timeout()
    {
        $store = [];

        (new DurationLimiter($this->redis(), 'key', 1, 1))->block(2, function () use (&$store) {
            $store[] = 1;
        });

        try {
            (new DurationLimiter($this->redis(), 'key', 1, 1))->block(0, function () use (&$store) {
                $store[] = 2;
            });
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LimiterTimeoutException::class, $e);
        }

        (new DurationLimiter($this->redis(), 'key', 1, 1))->block(2, function () use (&$store) {
            $store[] = 3;
        });

        $this->assertEquals([1, 3], $store);
    }

    private function redis()
    {
        return $this->redis['predis']->connection();
    }
}
