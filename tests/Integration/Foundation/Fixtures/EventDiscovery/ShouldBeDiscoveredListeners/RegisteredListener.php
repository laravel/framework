<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\ShouldBeDiscoveredListeners;

use Illuminate\Contracts\Events\ShouldBeDiscovered;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

class RegisteredListener implements ShouldBeDiscovered
{
    public static function shouldBeDiscovered(): bool
    {
        return true;
    }

    public function handle(EventOne $event)
    {
        //
    }
}
