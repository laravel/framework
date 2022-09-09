<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Timebox;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class SupportTimeboxTest extends TestCase
{
    public function testMakeExecutesCallback()
    {
        $callback = function () {
            $this->assertTrue(true);
        };

        (new Timebox)->make($callback, 0);
    }

    public function testMakeWaitsForMicroseconds()
    {
        $mock = m::spy(Timebox::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $mock->shouldReceive('usleep')->once();

        $mock->make(function () {}, 10000);

        $mock->shouldHaveReceived('usleep')->once();
    }

    public function testMakeShouldNotSleepWhenEarlyReturnHasBeenFlagged()
    {
        $mock = m::spy(Timebox::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $mock->make(function ($timebox) {
            $timebox->returnEarly();
        }, 10000);

        $mock->shouldNotHaveReceived('usleep');
    }

    public function testMakeShouldSleepWhenDontEarlyReturnHasBeenFlagged()
    {
        $mock = m::spy(Timebox::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $mock->shouldReceive('usleep')->once();

        $mock->make(function ($timebox) {
            $timebox->returnEarly();
            $timebox->dontReturnEarly();
        }, 10000);

        $mock->shouldHaveReceived('usleep')->once();
    }
}
