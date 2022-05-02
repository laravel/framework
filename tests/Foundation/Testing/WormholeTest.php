<?php

namespace Illuminate\Tests\Foundation\Testing;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\Wormhole;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\TestCase;

class WormholeTest extends TestCase
{
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

        // Restore the default Date Factory...
        Date::useDefault();
    }
}
