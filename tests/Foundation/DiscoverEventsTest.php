<?php

namespace Illuminate\Tests\Foundation;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Tests\Foundation\Fixtures\EventDiscovery\Events\EventOne;
use Illuminate\Tests\Foundation\Fixtures\EventDiscovery\Events\EventTwo;
use Illuminate\Tests\Foundation\Fixtures\EventDiscovery\Listeners\Listener;

class DiscoverEventsTest extends TestCase
{
    public function test_events_can_be_discovered()
    {
        class_alias(Listener::class, 'Tests\Foundation\Fixtures\EventDiscovery\Listeners\Listener');

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
