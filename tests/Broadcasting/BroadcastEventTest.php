<?php

namespace Illuminate\Tests\Broadcasting;

use Exception;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastingFactory;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastAfterCommit;
use Mockery;
use PHPUnit\Framework\TestCase;
use Throwable;

class BroadcastEventTest extends TestCase
{
    public function testBasicEventBroadcastParameterFormatting()
    {
        $broadcaster = Mockery::mock(Broadcaster::class);

        $broadcaster->expects('broadcast')->with(
            ['test-channel'], TestBroadcastEvent::class, ['firstName' => 'Taylor', 'lastName' => 'Otwell', 'collection' => ['foo' => 'bar']]
        );

        $manager = Mockery::mock(BroadcastingFactory::class);

        $manager->expects('connection')->with(null)->andReturn($broadcaster);

        $event = new TestBroadcastEvent;

        (new BroadcastEvent($event))->handle($manager);
    }

    public function testManualParameterSpecification()
    {
        $broadcaster = Mockery::mock(Broadcaster::class);

        $broadcaster->expects('broadcast')->with(
            ['test-channel'], TestBroadcastEventWithManualData::class, ['name' => 'Taylor', 'socket' => null]
        );

        $manager = Mockery::mock(BroadcastingFactory::class);

        $manager->expects('connection')->with(null)->andReturn($broadcaster);

        $event = new TestBroadcastEventWithManualData;

        (new BroadcastEvent($event))->handle($manager);
    }

    public function testSpecificBroadcasterGiven()
    {
        $broadcaster = Mockery::mock(Broadcaster::class);

        $broadcaster->expects('broadcast');

        $manager = Mockery::mock(BroadcastingFactory::class);

        $manager->expects('connection')->with('log')->andReturn($broadcaster);

        $event = new TestBroadcastEventWithSpecificBroadcaster;

        (new BroadcastEvent($event))->handle($manager);
    }

    public function testSpecificChannelsPerConnection()
    {
        $broadcaster = Mockery::mock(Broadcaster::class);

        $broadcaster->expects('broadcast')->with(
            ['first-channel'], TestBroadcastEventWithChannelsPerConnection::class, ['firstName' => 'Taylor', 'lastName' => 'Otwell', 'collection' => ['foo' => 'bar']]
        );

        $broadcaster->expects('broadcast')->with(
            ['second-channel'], TestBroadcastEventWithChannelsPerConnection::class, ['firstName' => 'Taylor']
        );

        $manager = Mockery::mock(BroadcastingFactory::class);

        $manager->expects('connection')->with('first_connection')->andReturn($broadcaster);
        $manager->expects('connection')->with('second_connection')->andReturn($broadcaster);

        $event = new TestBroadcastEventWithChannelsPerConnection;

        (new BroadcastEvent($event))->handle($manager);
    }

    public function testBroadcastAsStringIsUsedAsEventName()
    {
        $broadcaster = Mockery::mock(Broadcaster::class);

        $broadcaster->expects('broadcast')->with(
            ['test-channel'], 'custom-name', ['firstName' => 'Taylor', 'lastName' => 'Otwell', 'collection' => ['foo' => 'bar']]
        );

        $manager = Mockery::mock(BroadcastingFactory::class);

        $manager->expects('connection')->with(null)->andReturn($broadcaster);

        $event = new TestBroadcastEventWithStringName;

        (new BroadcastEvent($event))->handle($manager);
    }

    public function testBroadcastAsBackedEnumResolvesToValue()
    {
        $broadcaster = Mockery::mock(Broadcaster::class);

        $broadcaster->expects('broadcast')->with(
            ['test-channel'], 'custom-enum-name', ['firstName' => 'Taylor', 'lastName' => 'Otwell', 'collection' => ['foo' => 'bar']]
        );

        $manager = Mockery::mock(BroadcastingFactory::class);

        $manager->expects('connection')->with(null)->andReturn($broadcaster);

        $event = new TestBroadcastEventWithEnumName;

        (new BroadcastEvent($event))->handle($manager);
    }

    public function testMiddlewareProxiesMiddlewareFromUnderlyingEvent()
    {
        $event = new class
        {
            public function middleware(): array
            {
                return ['foo', 'bar'];
            }
        };

        $job = new BroadcastEvent($event);

        $this->assertSame(['foo', 'bar'], $job->middleware());
    }

    public function testDeletesWhenMissingModelsByDefault()
    {
        $job = new BroadcastEvent(new TestBroadcastEvent);

        $this->assertTrue($job->deleteWhenMissingModels);
    }

    public function testDeletingWhenMissingModelsCanBeDisabled()
    {
        $event = new class
        {
            public $deleteWhenMissingModels = false;
        };

        $job = new BroadcastEvent($event);

        $this->assertFalse($job->deleteWhenMissingModels);
    }

    public function testEventWithoutContractDefersToTheQueueConnectionDefault()
    {
        $job = new BroadcastEvent(new TestBroadcastEvent);

        $this->assertNull($job->afterCommit);
    }

    public function testEventWithoutContractRespectsAfterCommit()
    {
        $event = new class
        {
            public $afterCommit = true;
        };

        $job = new BroadcastEvent($event);

        $this->assertTrue($job->afterCommit);
    }

    public function testEventWithoutContractRespectsBeforeCommit()
    {
        $event = new class
        {
            public $afterCommit = false;
        };

        $job = new BroadcastEvent($event);

        $this->assertFalse($job->afterCommit);
    }

    public function testEventWithoutContractAndNullAfterCommitDefersToTheQueueConnectionDefault()
    {
        $event = new class
        {
            public $afterCommit = null;
        };

        $job = new BroadcastEvent($event);

        $this->assertNull($job->afterCommit);
    }

    public function testEventWithContractDefaultsToAfterCommit()
    {
        $event = new class extends TestBroadcastEvent implements ShouldBroadcastAfterCommit
        {
            //
        };

        $job = new BroadcastEvent($event);

        $this->assertTrue($job->afterCommit);
    }

    public function testEventWithContractAndAfterCommitFalseRespectsBeforeCommit()
    {
        $event = new class extends TestBroadcastEvent implements ShouldBroadcastAfterCommit
        {
            public $afterCommit = false;
        };

        $job = new BroadcastEvent($event);

        $this->assertFalse($job->afterCommit);
    }

    public function testEventWithContractAndExplicitAfterCommitTrueStillBroadcastsAfterCommit()
    {
        $event = new class extends TestBroadcastEvent implements ShouldBroadcastAfterCommit
        {
            public $afterCommit = true;
        };

        $job = new BroadcastEvent($event);

        $this->assertTrue($job->afterCommit);
    }

    public function testEventWithContractAndNullAfterCommitStillBroadcastsAfterCommit()
    {
        $event = new class extends TestBroadcastEvent implements ShouldBroadcastAfterCommit
        {
            public $afterCommit = null;
        };

        $job = new BroadcastEvent($event);

        $this->assertTrue($job->afterCommit);
    }

    public function testMiddlewareProxiesFailedHandlerFromUnderlyingEvent()
    {
        $event = new class
        {
            public function failed(?Throwable $e = null): void
            {
                $e->validateCall();
            }
        };

        $job = new BroadcastEvent($event);

        $exception = Mockery::mock(Exception::class);
        $exception->expects('validateCall');

        $job->failed($exception);
    }
}

class TestBroadcastEvent
{
    public $firstName = 'Taylor';
    public $lastName = 'Otwell';
    public $collection;
    private $title = 'Developer';

    public function __construct()
    {
        $this->collection = collect(['foo' => 'bar']);
    }

    public function broadcastOn()
    {
        return ['test-channel'];
    }
}

class TestBroadcastEventWithStringName extends TestBroadcastEvent
{
    public function broadcastAs()
    {
        return 'custom-name';
    }
}

class TestBroadcastEventWithEnumName extends TestBroadcastEvent
{
    public function broadcastAs()
    {
        return TestBroadcastEventName::Custom;
    }
}

enum TestBroadcastEventName: string
{
    case Custom = 'custom-enum-name';
}

class TestBroadcastEventWithManualData extends TestBroadcastEvent
{
    public function broadcastWith()
    {
        return ['name' => 'Taylor'];
    }
}

class TestBroadcastEventWithSpecificBroadcaster extends TestBroadcastEvent
{
    use InteractsWithBroadcasting;

    public function __construct()
    {
        $this->broadcastVia('log');
    }
}

class TestBroadcastEventWithChannelsPerConnection extends TestBroadcastEvent
{
    public function broadcastConnections()
    {
        return [
            'first_connection',
            'second_connection',
        ];
    }

    public function broadcastWith()
    {
        return [
            'first_connection' => [
                'firstName' => 'Taylor',
                'lastName' => 'Otwell',
                'collection' => ['foo' => 'bar'],
            ],
            'second_connection' => [
                'firstName' => 'Taylor',
            ],
        ];
    }

    public function broadcastOn()
    {
        return [
            'first_connection' => ['first-channel'],
            'second_connection' => ['second-channel'],
        ];
    }
}
