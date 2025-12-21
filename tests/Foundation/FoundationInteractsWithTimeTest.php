<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\Testing\Concerns\InteractsWithTime;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FoundationInteractsWithTimeTest extends TestCase
{
    use InteractsWithTime;

    public function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function testFreezeTimeReturnsFrozenTime()
    {
        $actual = $this->freezeTime();

        $this->assertTrue(Carbon::hasTestNow());
        $this->assertInstanceOf(\DateTimeInterface::class, $actual);
        $this->assertTrue(Carbon::getTestNow()->eq($actual));
    }

    public function testFreezeTimeReturnsCallbackResult()
    {
        $actual = $this->freezeTime(function () {
            return 12345;
        });

        $this->assertSame(12345, $actual);
        $this->assertFalse(Carbon::hasTestNow());
    }

    public function testFreezeTimeReturnsCallbackResultEvenWhenNull()
    {
        $actual = $this->freezeTime(function () {
            return null;
        });

        $this->assertNull($actual);
        $this->assertFalse(Carbon::hasTestNow());
    }

    public function testFreezeSecondReturnsFrozenTime()
    {
        $actual = $this->freezeSecond();

        $this->assertTrue(Carbon::hasTestNow());
        $this->assertInstanceOf(\DateTimeInterface::class, $actual);
        $this->assertTrue(Carbon::getTestNow()->eq($actual));
        $this->assertSame(0, $actual->milliseconds);
    }

    public function testFreezeSecondReturnsCallbackResult()
    {
        $actual = $this->freezeSecond(function () {
            return 12345;
        });

        $this->assertSame(12345, $actual);
        $this->assertFalse(Carbon::hasTestNow());
    }

    public function testFreezeSecondReturnsCallbackResultEvenWhenNull()
    {
        $actual = $this->freezeSecond(function () {
            return null;
        });

        $this->assertNull($actual);
        $this->assertFalse(Carbon::hasTestNow());
    }
}
