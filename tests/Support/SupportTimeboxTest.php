<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Timebox;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SupportTimeboxTest extends TestCase
{
    public function testMakeExecutesCallback()
    {
        $callback = function () {
            $this->assertTrue(true);
        };

        (new Timebox)->call($callback, 0);
    }

    public function testMakeWaitsForMicroseconds()
    {
        $mock = m::spy(Timebox::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $mock->shouldReceive('usleep')->once();

        $mock->call(function () {
        }, 10000);

        $mock->shouldHaveReceived('usleep')->once();
    }

    public function testMakeShouldNotSleepWhenEarlyReturnHasBeenFlagged()
    {
        $mock = m::spy(Timebox::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $mock->call(function ($timebox) {
            $timebox->returnEarly();
        }, 10000);

        $mock->shouldNotHaveReceived('usleep');
    }

    public function testMakeShouldSleepWhenDontEarlyReturnHasBeenFlagged()
    {
        $mock = m::spy(Timebox::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $mock->shouldReceive('usleep')->once();

        $mock->call(function ($timebox) {
            $timebox->returnEarly();
            $timebox->dontReturnEarly();
        }, 10000);

        $mock->shouldHaveReceived('usleep')->once();
    }
}
