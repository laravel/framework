<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\ShouldBeDiscoveredListeners;

use Illuminate\Events\Attributes\ShouldBeDiscovered;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

#[ShouldBeDiscovered]
class RegisteredByAttributeListener
{
    public function handle(EventOne $event)
    {
        //
    }
}
