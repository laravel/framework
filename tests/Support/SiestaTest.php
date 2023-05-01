<?php

namespace Illuminate\Tests\Support;

use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Siesta;
use PHPUnit\Event\Test\AssertionFailed;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SiestaTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Siesta::fake(false);

        Carbon::setTestNow();
    }

    public function testItSleepsForSeconds()
    {
        $start = microtime(true);
        Siesta::for(1)->seconds();
        $end = microtime(true);

        $this->assertEqualsWithDelta(1, $end - $start, 0.03);
    }

    public function testItSleepsForSecondsWithMilliseconds()
    {
        $start = microtime(true);
        Siesta::for(1.5)->seconds();
        $end = microtime(true);

        $this->assertEqualsWithDelta(1.5, $end - $start, 0.03);
    }

    public function testItCanFakeSleeping()
    {
        Siesta::fake();

        $start = microtime(true);
        Siesta::for(1.5)->seconds();
        $end = microtime(true);

        $this->assertEqualsWithDelta(0, $end - $start, 0.03);
    }

    public function testItCanSpecifyMinutes()
    {
        Siesta::fake();

        $siesta = Siesta::for(1.5)->minutes();

        $this->assertSame($siesta->duration->totalMicroseconds, 90_000_000);
    }

    public function testItCanSpecifyMinute()
    {
        Siesta::fake();

        $siesta = Siesta::for(1)->minute();

        $this->assertSame($siesta->duration->totalMicroseconds, 60_000_000);
    }

    public function testItCanSpecifySeconds()
    {
        Siesta::fake();

        $siesta = Siesta::for(1.5)->seconds();

        $this->assertSame($siesta->duration->totalMicroseconds, 1_500_000);
    }

    public function testItCanSpecifySecond()
    {
        Siesta::fake();

        $siesta = Siesta::for(1)->second();

        $this->assertSame($siesta->duration->totalMicroseconds, 1_000_000);
    }

    public function testItCanSpecifyMilliseconds()
    {
        Siesta::fake();

        $siesta = Siesta::for(1.5)->milliseconds();

        $this->assertSame($siesta->duration->totalMicroseconds, 1_500);
    }

    public function testItCanSpecifyMillisecond()
    {
        Siesta::fake();

        $siesta = Siesta::for(1)->millisecond();

        $this->assertSame($siesta->duration->totalMicroseconds, 1_000);
    }

    public function testItCanSpecifyMicroseconds()
    {
        Siesta::fake();

        $siesta = Siesta::for(1.5)->microseconds();

        // rounded as microseconds is the smallest unit supported...
        $this->assertSame($siesta->duration->totalMicroseconds, 1);
    }

    public function testItCanSpecifyMicrosecond()
    {
        Siesta::fake();

        $siesta = Siesta::for(1)->microsecond();

        $this->assertSame($siesta->duration->totalMicroseconds, 1);
    }

    public function testItCanChainDurations()
    {
        Siesta::fake();

        $siesta = Siesta::for(1)->second()
                      ->and(500)->microseconds();

        $this->assertSame($siesta->duration->totalMicroseconds, 1000500);
    }

    public function testItCanUseDateInterval()
    {
        Siesta::fake();

        $siesta = Siesta::for(CarbonInterval::seconds(1)->addMilliseconds(5));

        $this->assertSame($siesta->duration->totalMicroseconds, 1_005_000);
    }

    public function testItThrowsForUnknownTimeUnit()
    {
        try {
            Siesta::for(5);
            $this->fail();
        } catch (RuntimeException $e) {
            $this->assertSame('Unknown duration unit.', $e->getMessage());
        }
    }


    public function testItCanAssertSequence()
    {
        Siesta::fake();

        Siesta::for(5)->seconds();
        Siesta::for(1)->seconds()->and(5)->microsecond();

        Siesta::assertSequence([
            Siesta::for(5)->seconds(),
            Siesta::for(1)->seconds()->and(5)->microsecond(),
        ]);
    }

    public function testItFailsSequenceAssertion()
    {
        Siesta::fake();

        Siesta::for(5)->seconds();
        Siesta::for(1)->seconds()->and(5)->microseconds();

        try {
            Siesta::assertSequence([
                Siesta::for(5)->seconds(),
                Siesta::for(9)->seconds()->and(8)->milliseconds(),
            ]);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected siesta duration of [9 seconds 8 milliseconds] but instead found duration of [1 second 5 microseconds].\nFailed asserting that false is true.", $e->getMessage());
        }
    }

    public function testItCanUseSleep()
    {
        Siesta::fake();

        Siesta::sleep(3);

        Siesta::assertSequence([
            Siesta::for(3)->seconds(),
        ]);
    }

    public function testItCanUseUSleep()
    {
        Siesta::fake();

        Siesta::usleep(3);

        Siesta::assertSequence([
            Siesta::for(3)->microseconds(),
        ]);
    }

    public function testItCanSleepTillGivenTime()
    {
        Siesta::fake();
        Carbon::setTestNow(now()->startOfDay());

        Siesta::until(now()->addMinute());

        Siesta::assertSequence([
            Siesta::for(60)->seconds(),
        ]);
    }

    public function testItCanSleepTillGivenTimestamp()
    {
        Siesta::fake();
        Carbon::setTestNow(now()->startOfDay());

        Siesta::until(now()->addMinute()->timestamp);

        Siesta::assertSequence([
            Siesta::for(60)->seconds(),
        ]);
    }

    public function testItSleepsForZeroTimeWithNegativeDateTime()
    {
        Siesta::fake();
        Carbon::setTestNow(now()->startOfDay());

        Siesta::until(now()->subMinutes(100));

        Siesta::assertSequence([
            Siesta::for(0)->seconds(),
        ]);
    }

    public function testSleepingForZeroTime()
    {
        Siesta::fake();

        Siesta::for(0)->seconds();

        try {
            Siesta::assertSequence([
                Siesta::for(1)->seconds(),
            ]);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected siesta duration of [1 second] but instead found duration of [0 microseconds].\nFailed asserting that false is true.", $e->getMessage());
        }
    }

    public function testItFailsWhenSequenceContainsTooManySiestas()
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
            $this->assertSame("Expected [2] siestas but found [1].\nFailed asserting that 1 is identical to 2.", $e->getMessage());
        }
    }

    public function testSilentlySetsDurationToZeroForNegativeValues()
    {
        Siesta::fake();

        Siesta::for(-1)->seconds();

        Siesta::assertSequence([
            Siesta::for(0)->seconds(),
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
            $this->assertSame("Expected [2] siestas but found [1].\nFailed asserting that 1 is identical to 2.", $e->getMessage());
        }
    }

    public function testItCanAssertNoSleepingOccurred()
    {
        Siesta::fake();

        Siesta::assertInsomniac();

        Siesta::for(0)->second();

        // we still have not slept...
        Siesta::assertInsomniac();

        Siesta::for(1)->second();

        try {
            Siesta::assertInsomniac();
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Unexpected siesta duration of [1 second] found.\nFailed asserting that 1000000 is identical to 0.", $e->getMessage());
        }
    }

    public function testItCanAssertSleepCount()
    {
        Siesta::fake();

        Siesta::assertSleptTimes(0);

        Siesta::for(1)->second();

        Siesta::assertSleptTimes(1);

        try {
            Siesta::assertSleptTimes(0);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected [0] siestas but found [1].\nFailed asserting that 1 is identical to 0.", $e->getMessage());
        }

        try {
            Siesta::assertSleptTimes(2);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("Expected [2] siestas but found [1].\nFailed asserting that 1 is identical to 2.", $e->getMessage());
        }
    }

    public function testAssertSlept()
    {
        Siesta::fake();

        Siesta::assertSlept(fn () => true, 0);

        try {
            Siesta::assertSlept(fn () => true);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("The expected siesta was found [0] times instead of [1].\nFailed asserting that 0 is identical to 1.", $e->getMessage());
        }

        Siesta::for(5)->seconds();

        Siesta::assertSlept(fn (CarbonInterval $duration) => $duration->totalSeconds === 5);

        try {
            Siesta::assertSlept(fn (CarbonInterval $duration) => $duration->totalSeconds === 5, 2);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("The expected siesta was found [1] times instead of [2].\nFailed asserting that 1 is identical to 2.", $e->getMessage());
        }

        try {
            Siesta::assertSlept(fn (CarbonInterval $duration) => $duration->totalSeconds === 6);
            $this->fail();
        } catch (AssertionFailedError $e) {
            $this->assertSame("The expected siesta was found [0] times instead of [1].\nFailed asserting that 0 is identical to 1.", $e->getMessage());
        }
    }
}
