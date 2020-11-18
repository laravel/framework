<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\Wormhole;
use PHPUnit\Framework\TestCase;

class WormholeTest extends TestCase
{
    public function testCanTravelBackToPresent()
    {
        // Preserve the timelines we want to compare the reality with..
        $present = now();
        $future = now()->addDays(10);

        // Travel in time..
        (new Wormhole(10))->days();

        // Assert we are now in the future..
        $this->assertEquals($future->format('Y-m-d'), now()->format('Y-m-d'));

        // Assert we can go back to the present..
        $this->assertEquals($present->format('Y-m-d'), Wormhole::back()->format('Y-m-d'));
    }
}
