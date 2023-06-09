<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventOne;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Events\EventTwo;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\AbstractListener;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\Listener;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\Listeners\ListenerInterface;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\namespaces\app\Listener as AppListener;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\namespaces\domain\Listener as DomainListener;
use Illuminate\Tests\Integration\Foundation\Fixtures\EventDiscovery\UnionListeners\UnionListener;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

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

    public function testEventsCanBeDiscoveredInDifferentNamespaces()
    {
        (new ReflectionClass(DiscoverEvents::class))
            ->getProperty('namespaces')
            ->setValue([
                'App\\' => 'tests/Integration/Foundation/Fixtures/EventDiscovery/namespaces/app/',
                'Domain\\' => 'tests/Integration/Foundation/Fixtures/EventDiscovery/namespaces/domain/',
            ]);

        class_alias(AppListener::class, 'App\Listener');
        class_alias(DomainListener::class, 'Domain\Listener');

        $events = DiscoverEvents::within(__DIR__.'/Fixtures/EventDiscovery/namespaces', getcwd());

        $this->assertEqualsCanonicalizing([
            EventOne::class => [
                AppListener::class.'@handle',
                AppListener::class.'@handleEventOne',
                DomainListener::class.'@handle',
                DomainListener::class.'@handleEventOne',
            ],
            EventTwo::class => [
                AppListener::class.'@handleEventTwo',
                DomainListener::class.'@handleEventTwo',
            ],
        ], $events);
    }
}
