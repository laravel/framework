<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\ShouldBeDiscoveredListeners;

use Illuminate\Contracts\Events\ShouldBeDiscovered;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

class SkippedListener implements ShouldBeDiscovered
{
    public static function shouldBeDiscovered(): bool
    {
        return false;
    }

    public function handle(EventOne $event)
    {
        //
    }
}
