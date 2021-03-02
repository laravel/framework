<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventTwo;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\AbstractListener;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\Listener;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\ListenerInterface;
use Orchestra\Testbench\TestCase;

class DiscoverEventsTest extends TestCase
{
    public function testEventsCanBeDiscovered()
    {
        class_alias(Listener::class, 'Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\Listener');
        class_alias(AbstractListener::class, 'Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\AbstractListener');
        class_alias(ListenerInterface::class, 'Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\ListenerInterface');

        $events = DiscoverEvents::within(__DIR__.'/Fixtures/EventDiscovery/Listeners', 'Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners');

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

    public function testListenerNamespace()
    {
        $eventServiceProvider = new EventServiceProvider($this->app);
        $listenerWithIn = tap(new \ReflectionMethod($eventServiceProvider, 'discoverEventsWithin'))
            ->setAccessible(true)
            ->invoke($eventServiceProvider);

        $this->assertEquals([
            $this->app->path('Listeners') => 'App\Listeners',
        ], $listenerWithIn);
    }
}
