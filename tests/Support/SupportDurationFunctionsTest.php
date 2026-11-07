<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;

use function Illuminate\Support\days;
use function Illuminate\Support\hours;
use function Illuminate\Support\minutes;
use function Illuminate\Support\seconds;

class SupportDurationFunctionsTest extends TestCase
{
    public function testDurationFunctionsAcceptIntegers()
    {
        $this->assertEquals(90, seconds(90)->totalSeconds);
        $this->assertEquals(300, minutes(5)->totalSeconds);
        $this->assertEquals(7200, hours(2)->totalSeconds);
        $this->assertEquals(172800, days(2)->totalSeconds);
    }

    public function testDurationFunctionsKeepFractionalValues()
    {
        $this->assertEquals(1400, seconds(1.4)->totalMilliseconds);
        $this->assertEquals(90, minutes(1.5)->totalSeconds);
        $this->assertEquals(1800, hours(0.5)->totalSeconds);
        $this->assertEquals(36, days(1.5)->totalHours);
    }
}
