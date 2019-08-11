<?php

namespace Illuminate\Tests\Integration\Foundation;

use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventTwo;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\Listener;
use Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\Listener as ListenerAlias;

class DiscoverEventsTest extends TestCase
{
    public function test_events_can_be_discovered()
    {
        class_alias(Listener::class, ListenerAlias::class);

        $events = DiscoverEvents::within(__DIR__.'/Fixtures/EventDiscovery/Listeners', getcwd());

        $this->assertEquals([
            EventOne::class => [
                Listener::class.'@handle',
                Listener::class.'@handleEventOne',
            ],
            EventTwo::class => [
                Listener::class.'@handleEventTwo',
            ],
        ], $events);
    }
}
