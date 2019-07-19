<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Stopwatch;
use PHPUnit\Framework\TestCase;

class SupportStopwatchTest extends TestCase
{
    public function testTimeBetweenChecksCanBeMeasured()
    {
        $stopwatch = new Stopwatch;
        $stopwatch->start('foo');
        usleep(10 * 1000);
        $difference = $stopwatch->check('foo');
        // Make sure the millisecond difference is within a normal range of variance...
        $this->assertGreaterThan(0, $difference);
    }
}
