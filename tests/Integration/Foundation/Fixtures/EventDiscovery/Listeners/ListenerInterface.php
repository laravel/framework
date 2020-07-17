<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners;

use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;

interface ListenerInterface
{
    public function handle(EventOne $event);
}
