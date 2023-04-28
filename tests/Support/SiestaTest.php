<?php

namespace Illuminate\Tests\Support;

use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Siesta;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SiestaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Siesta::fake(false);
    }

    public function testItSleepsForSeconds()
    {
        $start = Carbon::now();
        Siesta::for(1)->seconds();
        $end = Carbon::now();

        $this->assertTrue($start->toImmutable()->addSecond()->isBefore($end));
        $this->assertTrue($start->toImmutable()->addSecond()->addMilliseconds(20)->isAfter($end));
    }

    public function testItSleepsForSecondsWithMilliseconds()
    {
        $start = Carbon::now();
        Siesta::for(1.5)->seconds();
        $end = Carbon::now();

        $this->assertTrue($start->toImmutable()->addSecond()->addMilliseconds(500)->isBefore($end));
        $this->assertTrue($start->toImmutable()->addSecond()->addMilliseconds(520)->isAfter($end));
    }

    public function testItCanSpecifyMinutes()
    {
        $pause = Siesta::for(1.5)->minutes();

        $this->assertSame($pause->duration->totalSeconds, 90);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifySeconds()
    {
        $pause = Siesta::for(5)->seconds();

        $this->assertSame($pause->duration->totalSeconds, 5);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifySecond()
    {
        $pause = Siesta::for(1)->second();

        $this->assertSame($pause->duration->totalSeconds, 1);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifyMilliseconds()
    {
        $pause = Siesta::for(5)->milliseconds();

        $this->assertSame($pause->duration->totalMilliseconds, 5);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifyMillisecond()
    {
        $pause = Siesta::for(1)->milliseconds();

        $this->assertSame($pause->duration->totalMilliseconds, 1);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifyMicroseconds()
    {
        $pause = Siesta::for(5)->microseconds();

        $this->assertSame($pause->duration->totalMicroseconds, 5);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanSpecifyMicrosecond()
    {
        $pause = Siesta::for(1)->millisecond();

        $this->assertSame($pause->duration->totalMilliseconds, 1);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanChainDurations()
    {
        $pause = Siesta::for(1)->second()->and(500)->microseconds();

        $this->assertSame($pause->duration->totalMicroseconds, 1000500);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItCanPassDateInterval()
    {
        $pause = Siesta::for(CarbonInterval::seconds(3));

        $this->assertSame($pause->duration->totalSeconds, 3);

        $pause->duration = CarbonInterval::seconds(0);
    }

    public function testItThrowsOnUnknownTimeUnit()
    {
        try {
            Siesta::for(5);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertSame('Unknown Siesta duration unit.', $e->getMessage());
        }
    }

    public function testItCanFakeSleep()
    {
        Siesta::fake();

        $start = Carbon::now();
        Siesta::for(5)->seconds();
        $end = Carbon::now();

        $this->assertTrue($start->toImmutable()->addMilliseconds(20)->isAfter($end));
    }

    public function testItCanAssertPauseSequences()
    {
        Siesta::fake();

        Siesta::for(5)->seconds();
        Siesta::for(1)->seconds()->and(5)->microsecond();

        Siesta::assertSequence([
            Siesta::for(5)->seconds(),
            Siesta::for(1)->seconds()->and(5)->microsecond(),
        ]);
    }

    public function testItCanFailAssertions()
    {
        Siesta::fake();

        Siesta::for(5)->seconds();
        Siesta::for(1)->seconds()->and(5)->microsecond();

        try {
            Siesta::assertSequence([
                Siesta::for(5)->seconds(),
                Siesta::for(5)->seconds(),
            ]);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected pause of [5 seconds] but instead found pause of [1 second].\nFailed asserting that false is true.", $e->getMessage());
        }
    }

    public function testItCanCallSleepDirectly()
    {
        Siesta::fake();

        Siesta::sleep(3);

        Siesta::assertSequence([
            Siesta::for(3)->seconds(),
        ]);
    }

    public function testItCanCallUSleepDirectly()
    {
        Siesta::fake();

        Siesta::usleep(3);

        Siesta::assertSequence([
            Siesta::for(3)->microseconds(),
        ]);
    }

    public function testItCanSleepTillGivenTime()
    {
        Carbon::setTestNow(now()->startOfDay());
        Siesta::fake();

        Siesta::until(now()->addMinute());

        Siesta::assertSequence([
            Siesta::for(60)->seconds(),
        ]);
    }

    public function testItSleepsForZeroTimeIfTimeHasAlreadyPast()
    {
        Siesta::fake();
        Carbon::setTestNow(now()->startOfDay());

        Siesta::until(now()->subMinute());

        Siesta::assertSequence([
            Siesta::for(0)->seconds(),
        ]);
    }

    public function testEmptyDiff()
    {
        Siesta::fake();

        Siesta::for(0)->seconds();

        try {
            Siesta::assertSequence([
                Siesta::for(1)->seconds(),
            ]);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected pause of [1 second] but instead found pause of [0 seconds].\nFailed asserting that false is true.", $e->getMessage());
        }
    }

    public function testMoreAssertionsThanPauses()
    {
        Siesta::fake();

        Siesta::for(1)->seconds();

        try {
            Siesta::assertSequence([
                Siesta::for(1)->seconds(),
                Siesta::for(1)->seconds(),
            ]);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected [2] pauses but only found [1].\nFailed asserting that false is true.", $e->getMessage());
        }
    }

    public function testMorePausesThanAssertions()
    {
        Siesta::fake();

        Siesta::for(1)->seconds();
        Siesta::for(2)->seconds();

        Siesta::assertSequence([
            Siesta::for(1)->seconds(),
        ]);
    }

    public function testItDoesntSleepForNegativeDurations()
    {
        Siesta::fake();

        Siesta::for(-1)->seconds();

        Siesta::assertSequence([
            Siesta::for(0)->seconds(),
        ]);
    }

    public function testItDoesSleepWhenNegativeValuesAreChainedToPositive()
    {
        Siesta::fake();

        Siesta::for(-1)->seconds()->and(5)->seconds();

        Siesta::assertSequence([
            Siesta::for(4)->seconds(),
        ]);
    }

    public function testItDoesntCaptureAssertionInstances()
    {
        Siesta::fake();

        Siesta::for(1)->second();

        Siesta::assertSequence([
            Siesta::for(1)->second(),
        ]);

        try {
            Siesta::assertSequence([
                Siesta::for(1)->second(),
                Siesta::for(1)->second(),
            ]);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected [2] pauses but only found [1].\nFailed asserting that false is true.", $e->getMessage());
        }
    }

    public function testItCanAssertNoSleepingOccurred()
    {
        Siesta::fake();

        Siesta::assertInsomniac();

        Siesta::for(1)->second();

        try {
            Siesta::assertInsomniac();
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected [0] pauses but found [1].\nFailed asserting that 1 is identical to 0.", $e->getMessage());
        }
    }

    public function testItCanAssertSleepCount()
    {
        Siesta::fake();

        Siesta::assertSleptTimes(0);

        Siesta::for(1)->second();

        Siesta::assertSleptTimes(1);
        try {
            Siesta::assertSleptTimes(2);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected [2] pauses but found [1].\nFailed asserting that 1 is identical to 2.", $e->getMessage());
        }
    }
}
