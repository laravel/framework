<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventTwo;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\AbstractListener;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\Listener;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\ListenerInterface;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\UnionListeners\UnionListener;
use Orchestra\Testbench\TestCase;

class DiscoverEventsTest extends TestCase
{
    public function testEventsCanBeDiscovered()
    {
        class_alias(Listener::class, 'Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\Listener');
        class_alias(AbstractListener::class, 'Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\AbstractListener');
        class_alias(ListenerInterface::class, 'Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\ListenerInterface');

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

    public function testUnionEventsCanBeDiscovered()
    {
        class_alias(UnionListener::class, 'Tests\Integration\Foundation\Fixtures\EventDiscovery\UnionListeners\UnionListener');

        $events = DiscoverEvents::within(__DIR__.'/Fixtures/EventDiscovery/UnionListeners', getcwd());

        $this->assertEquals([
            EventOne::class => [
                UnionListener::class.'@handle',
            ],
            EventTwo::class => [
                UnionListener::class.'@handle',
            ],
        ], $events);
    }
}
