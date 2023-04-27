<?php

namespace Illuminate\Tests\Support;

use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Pause;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PauseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Pause::fake(false);
    }

    public function testItSleepForSeconds()
    {
        $start = Carbon::now();
        Pause::for(1)->seconds();
        $end = Carbon::now();

        $this->assertTrue($start->toImmutable()->addSecond()->isBefore($end));
        $this->assertTrue($start->toImmutable()->addSecond()->addMilliseconds(20)->isAfter($end));
    }

    public function testItSleepsForSecondsWithMilliseconds()
    {
        $start = Carbon::now();
        Pause::for(1.5)->seconds();
        $end = Carbon::now();

        $this->assertTrue($start->toImmutable()->addSecond()->addMilliseconds(500)->isBefore($end));
        $this->assertTrue($start->toImmutable()->addSecond()->addMilliseconds(520)->isAfter($end));
    }

    public function testItCanSpecifyMinutes()
    {
        $pause = Pause::for(1.5)->minutes();

        $this->assertSame($pause->duration->totalSeconds, 90);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifySeconds()
    {
        $pause = Pause::for(5)->seconds();

        $this->assertSame($pause->duration->totalSeconds, 5);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifySecond()
    {
        $pause = Pause::for(1)->second();

        $this->assertSame($pause->duration->totalSeconds, 1);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifyMilliseconds()
    {
        $pause = Pause::for(5)->milliseconds();

        $this->assertSame($pause->duration->totalMilliseconds, 5);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifyMillisecond()
    {
        $pause = Pause::for(1)->milliseconds();

        $this->assertSame($pause->duration->totalMilliseconds, 1);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifyMicroseconds()
    {
        $pause = Pause::for(5)->microseconds();

        $this->assertSame($pause->duration->totalMicroseconds, 5);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifyMicrosecond()
    {
        $pause = Pause::for(1)->millisecond();

        $this->assertSame($pause->duration->totalMilliseconds, 1);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanChainDurations()
    {
        $pause = Pause::for(1)->second()->and(500)->microseconds();

        $this->assertSame($pause->duration->totalMicroseconds, 1000500);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanPassDateInterval()
    {
        $pause = Pause::for(CarbonInterval::seconds(3));

        $this->assertSame($pause->duration->totalSeconds, 3);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItThrowsOnUnknownTimeUnit()
    {
        try {
            Pause::for(5);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertSame('Unknown pause time unit.', $e->getMessage());
        }
    }

    public function testItCanFakeSleep()
    {
        Pause::fake();

        $start = Carbon::now();
        Pause::for(5)->seconds();
        $end = Carbon::now();

        $this->assertTrue($start->toImmutable()->addMilliseconds(20)->isAfter($end));
    }

    public function testItCanAssertPauseSequences()
    {
        Pause::fake();

        Pause::for(5)->seconds();
        Pause::for(1)->seconds()->and(5)->microsecond();

        Pause::assertSequence([
            Pause::for(5)->seconds(),
            Pause::for(1)->seconds()->and(5)->microsecond(),
        ]);
    }

    public function testItCanFailAssertions()
    {
        Pause::fake();

        Pause::for(5)->seconds();
        Pause::for(1)->seconds()->and(5)->microsecond();

        try {
            Pause::assertSequence([
                Pause::for(5)->seconds(),
                Pause::for(5)->seconds(),
            ]);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected pause of [5 seconds] but instead found pause of [1 second].\nFailed asserting that false is true.", $e->getMessage());
        }
    }
}

