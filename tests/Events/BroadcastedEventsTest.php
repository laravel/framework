<?php

namespace Illuminate\Tests\Events;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\PendingBroadcast;
use Illuminate\Container\Container;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Events\Dispatcher;
use Illuminate\Tests\Events\Fixtures\ExampleEvent;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;
use stdClass;

class BroadcastedEventsTest extends TestCase
{
    use VerifiesDoubles;

    protected function exposedDispatcher(): Dispatcher
    {
        return new class extends Dispatcher
        {
            public function shouldBroadcast(array $payload)
            {
                return parent::shouldBroadcast($payload);
            }
        };
    }

    public function testShouldBroadcastSuccess()
    {
        $d = $this->exposedDispatcher();

        $event = new BroadcastEvent;

        $this->assertTrue($d->shouldBroadcast([$event]));

        $event = new AlwaysBroadcastEvent;

        $this->assertTrue($d->shouldBroadcast([$event]));
    }

    public function testShouldBroadcastAsQueuedAndCallNormalListeners()
    {
        unset($_SERVER['__event.test']);
        $container = Double::for(Container::class, override: true);
        $d = new Dispatcher($container->instance());
        $broadcast = Double::for(BroadcastManager::class);
        $broadcast->expects('queue');
        $container->expects('make')->with(BroadcastFactory::class)->returns($broadcast);

        $d->listen(AlwaysBroadcastEvent::class, function ($payload) {
            $_SERVER['__event.test'] = $payload;
        });

        $d->dispatch($e = new AlwaysBroadcastEvent);

        $this->assertSame($e, $_SERVER['__event.test']);
    }

    public function testShouldBroadcastFail()
    {
        $d = $this->exposedDispatcher();

        $event = new BroadcastFalseCondition;

        $this->assertFalse($d->shouldBroadcast([$event]));

        $event = new ExampleEvent;

        $this->assertFalse($d->shouldBroadcast([$event]));
    }

    public function testBroadcastWithMultipleChannels()
    {
        $container = Double::for(Container::class, override: true);
        $d = new Dispatcher($container->instance());
        $broadcast = Double::for(BroadcastManager::class);
        $broadcast->expects('queue');
        $container->expects('make')->with(BroadcastFactory::class)->returns($broadcast);

        $event = new class implements ShouldBroadcast
        {
            public function broadcastOn()
            {
                return ['channel-1', 'channel-2'];
            }
        };

        $d->dispatch($event);
    }

    public function testBroadcastWithCustomConnectionName()
    {
        $container = Double::for(Container::class, override: true);
        $d = new Dispatcher($container->instance());
        $broadcast = Double::for(BroadcastManager::class);
        $broadcast->expects('queue');
        $container->expects('make')->with(BroadcastFactory::class)->returns($broadcast);

        $event = new class implements ShouldBroadcast
        {
            public $connection = 'custom-connection';

            public function broadcastOn()
            {
                return ['test-channel'];
            }
        };

        $d->dispatch($event);
    }

    public function testBroadcastWithCustomEventName()
    {
        $container = Double::for(Container::class, override: true);
        $d = new Dispatcher($container->instance());
        $broadcast = Double::for(BroadcastManager::class);
        $broadcast->expects('queue');
        $container->expects('make')->with(BroadcastFactory::class)->returns($broadcast);

        $event = new class implements ShouldBroadcast
        {
            public function broadcastOn()
            {
                return ['test-channel'];
            }

            public function broadcastAs()
            {
                return 'custom-event-name';
            }
        };

        $d->dispatch($event);
    }

    public function testBroadcastWithCustomPayload()
    {
        $container = Double::for(Container::class, override: true);
        $d = new Dispatcher($container->instance());
        $broadcast = Double::for(BroadcastManager::class);
        $broadcast->expects('queue');
        $container->expects('make')->with(BroadcastFactory::class)->returns($broadcast);

        $event = new class implements ShouldBroadcast
        {
            public $customData = 'test-data';

            public function broadcastOn()
            {
                return ['test-channel'];
            }

            public function broadcastWith()
            {
                return ['custom' => $this->customData];
            }
        };

        $d->dispatch($event);
    }

    public function testEventBroadcastsUsingNamedArguments()
    {
        $container = new Container;
        $broadcast = Double::for(BroadcastManager::class);
        $container->instance(BroadcastFactory::class, $broadcast);

        $originalContainer = Container::getInstance();
        Container::setInstance($container);

        try {
            $pendingBroadcast = new PendingBroadcastWithoutDestructor(new Dispatcher, new stdClass);

            $broadcast->expects('event')->with(Argument::satisfies(function ($event) {
                $this->assertInstanceOf(BroadcastableNamedArgumentsEvent::class, $event);
                $this->assertSame('first-value', $event->first);
                $this->assertSame('second-value', $event->second);

                return true;
            }))->returns($pendingBroadcast);

            $this->assertSame(
                $pendingBroadcast,
                BroadcastableNamedArgumentsEvent::broadcast(second: 'second-value', first: 'first-value')
            );
        } finally {
            Container::setInstance($originalContainer);
        }
    }
}

class PendingBroadcastWithoutDestructor extends PendingBroadcast
{
    public function __destruct()
    {
        // Prevent the event from being dispatched
    }
}

class BroadcastEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return ['test-channel'];
    }

    public function broadcastWhen()
    {
        return true;
    }
}

class AlwaysBroadcastEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        return ['test-channel'];
    }
}

class BroadcastFalseCondition extends BroadcastEvent
{
    public function broadcastWhen()
    {
        return false;
    }
}

class BroadcastableNamedArgumentsEvent
{
    use \Illuminate\Foundation\Events\Dispatchable;

    public function __construct(
        public string $first,
        public string $second,
    ) {
    }
}
