<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\ShouldBeDiscoveredListeners;

use Illuminate\Events\Attributes\ShouldBeDiscovered;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

#[ShouldBeDiscovered(false)]
class SkippedByAttributeListener
{
    public function handle(EventOne $event)
    {
        //
    }
}
