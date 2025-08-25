<?php

namespace Illuminate\Tests\Foundation\Testing;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\Wormhole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\TestCase;

class WormholeTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Date::useDefault();
    }

    public function testCanTravelBackToPresent()
    {
        // Preserve the timelines we want to compare the reality with...
        $present = now();
        $future = now()->addDays(10);

        // Travel in time...
        (new Wormhole(10))->days();

        // Assert we are now in the future...
        $this->assertEquals($future->format('Y-m-d'), now()->format('Y-m-d'));

        // Assert we can go back to the present...
        $this->assertEquals($present->format('Y-m-d'), Wormhole::back()->format('Y-m-d'));
    }

    public function testCarbonImmutableCompatibility()
    {
        // Tell the Date Factory to use CarbonImmutable...
        Date::use(CarbonImmutable::class);

        // Record what time it is in 10 days...
        $present = now();
        $future = $present->addDays(10);

        // Travel in time...
        (new Wormhole(10))->days();

        // Assert that the present time didn't get mutated...
        $this->assertNotEquals($future->format('Y-m-d'), $present->format('Y-m-d'));

        // Assert the time travel was successful...
        $this->assertEquals($future->format('Y-m-d'), now()->format('Y-m-d'));
    }

    public function testItCanTravelByMicroseconds()
    {
        Carbon::setTestNow(Carbon::parse('2000-01-01 00:00:00')->startOfSecond());

        (new Wormhole(1))->microsecond();
        $this->assertSame('2000-01-01 00:00:00.000001', Date::now()->format('Y-m-d H:i:s.u'));

        (new Wormhole(5))->microseconds();
        $this->assertSame('2000-01-01 00:00:00.000006', Date::now()->format('Y-m-d H:i:s.u'));

        Carbon::setTestnow();
    }
}
