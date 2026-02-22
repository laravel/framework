<?php

namespace Illuminate\Tests\Events;

use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueuedEventsTest extends TestCase
{
    public function testQueuedEventHandlersAreQueued()
    {
        $d = new Dispatcher;
        $queue = m::mock(Queue::class);

        $queue->shouldReceive('connection')->once()->with(null)->andReturnSelf();

        $queue->shouldReceive('pushOn')->once()->with(null, m::type(CallQueuedListener::class));

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', TestDispatcherQueuedHandler::class.'@someMethod');
        $d->dispatch('some.event', ['foo', 'bar']);
    }

    public function testCustomizedQueuedEventHandlersAreQueued()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherConnectionQueuedHandler::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('my_queue', CallQueuedListener::class);
    }

    public function testQueueIsSetByGetQueue()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherGetQueue::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('some_other_queue', CallQueuedListener::class);
    }

    public function testQueueIsSetByGetConnection()
    {
        $d = new Dispatcher;
        $queue = m::mock(Queue::class);

        $queue->shouldReceive('connection')->once()->with('some_other_connection')->andReturnSelf();

        $queue->shouldReceive('pushOn')->once()->with(null, m::type(CallQueuedListener::class));

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', TestDispatcherGetConnection::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);
    }

    public function testDelayIsSetByWithDelay()
    {
        $d = new Dispatcher;
        $queue = m::mock(Queue::class);

        $queue->shouldReceive('connection')->once()->with(null)->andReturnSelf();

        $queue->shouldReceive('laterOn')->once()->with(null, 20, m::type(CallQueuedListener::class));

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', TestDispatcherGetDelay::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);
    }

    public function testQueueIsSetByGetQueueDynamically()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherGetQueueDynamically::class.'@handle');
        $d->dispatch('some.event', [['useHighPriorityQueue' => true], 'bar']);

        $fakeQueue->assertPushedOn('p0', CallQueuedListener::class);
    }

    public function testQueueIsSetByGetConnectionDynamically()
    {
        $d = new Dispatcher;
        $queueManager = $this->createMock(QueueManager::class);
        $queue = $this->createMock(Queue::class);

        $queueManager->expects($this->once())
            ->method('connection')
            ->with('redis')
            ->willReturn($queue);

        $queue->expects($this->once())
            ->method('pushOn')
            ->with(null, $this->isInstanceOf(CallQueuedListener::class));

        $d->setQueueResolver(function () use ($queueManager) {
            return $queueManager;
        });

        $d->listen('some.event', TestDispatcherGetConnectionDynamically::class.'@handle');
        $d->dispatch('some.event', [
            ['shouldUseRedisConnection' => true],
            'bar',
        ]);
    }

    public function testQueueIsSetUsingQueueRoutes()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $queueRoutes = new QueueRoutes;
        $queueRoutes->set(TestDispatcherQueueRoutes::class, 'event-queue', 'event-connection');
        $container->instance('queue.routes', $queueRoutes);

        $fakeQueue = new QueueFake($container);

        Container::setInstance($container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherQueueRoutes::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->connection('event-connection')->assertPushedOn('event-queue', CallQueuedListener::class);

        Container::setInstance(null);
    }

    public function testDelayIsSetByWithDelayDynamically()
    {
        $d = new Dispatcher;
        $queue = m::mock(Queue::class);

        $queue->shouldReceive('connection')->once()->with(null)->andReturnSelf();

        $queue->shouldReceive('laterOn')->once()->with(null, 60, m::type(CallQueuedListener::class));

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', TestDispatcherGetDelayDynamically::class.'@handle');
        $d->dispatch('some.event', [['useHighDelay' => true], 'bar']);
    }

    public function testQueuePropagateRetryUntilAndMaxExceptions()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherOptions::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->maxExceptions === 1 && $job->retryUntil !== null;
        });
    }

    public function testQueuePropagateTries()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherOptions::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->tries === 5;
        });
    }

    public function testQueuePropagateMessageGroupProperty()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherWithMessageGroupProperty::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->messageGroup === 'group-property';
        });
    }

    public function testQueuePropagateMessageGroupMethodOverProperty()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherWithMessageGroupMethod::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->messageGroup === 'group-method';
        });
    }

    public function testQueuePropagateDeduplicationIdMethod()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherWithDeduplicationIdMethod::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            $this->assertInstanceOf(SerializableClosure::class, $job->deduplicator);

            return is_callable($job->deduplicator) && call_user_func($job->deduplicator, '', null) === 'deduplication-id-method';
        });
    }

    public function testQueuePropagateDeduplicatorMethodOverDeduplicationIdMethod()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherWithDeduplicatorMethod::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            $this->assertInstanceOf(SerializableClosure::class, $job->deduplicator);

            return is_callable($job->deduplicator) && call_user_func($job->deduplicator, '', null) === 'deduplicator-method';
        });
    }

    public function testQueuePropagateMiddleware()
    {
        $d = new Dispatcher;

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherMiddleware::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return count($job->middleware) === 1
                && $job->middleware[0] instanceof TestMiddleware
                && $job->middleware[0]->a === 'foo'
                && $job->middleware[0]->b === 'bar';
        });
    }

    public function testDispatchesOnQueueDefinedWithEnum()
    {
        $d = new Dispatcher;
        $queue = m::mock(Queue::class);

        $fakeQueue = new QueueFake(new Container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherViaQueueSupportsEnum::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushedOn('enumerated-queue', CallQueuedListener::class);
    }

    public function testQueuePropagatesShouldBeUnique()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $cache = m::mock(Cache::class);
        $lock = m::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $cache->shouldReceive('lock')->once()->andReturn($lock);
        $lock->shouldReceive('get')->once()->andReturn(true);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherShouldBeUnique::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->shouldBeUnique === true
                && $job->shouldBeUniqueUntilProcessing === false
                && $job->uniqueId === 'unique-listener-id'
                && $job->uniqueFor === 60;
        });
    }

    public function testUniqueListenerNotQueuedWhenLockNotAcquired()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $cache = m::mock(Cache::class);
        $lock = m::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $cache->shouldReceive('lock')->once()->andReturn($lock);
        $lock->shouldReceive('get')->once()->andReturn(false);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherShouldBeUnique::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertNothingPushed();
    }

    public function testQueuePropagatesShouldBeUniqueUntilProcessing()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $cache = m::mock(Cache::class);
        $lock = m::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $cache->shouldReceive('lock')->once()->andReturn($lock);
        $lock->shouldReceive('get')->once()->andReturn(true);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherShouldBeUniqueUntilProcessing::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->shouldBeUnique === true
                && $job->shouldBeUniqueUntilProcessing === true;
        });
    }

    public function testQueuePropagatesUniqueIdFromMethod()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $cache = m::mock(Cache::class);
        $lock = m::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $cache->shouldReceive('lock')->once()->andReturn($lock);
        $lock->shouldReceive('get')->once()->andReturn(true);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherUniqueIdFromMethod::class.'@handle');
        $d->dispatch('some.event', [['id' => 'event-123'], 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) {
            return $job->uniqueId === 'unique-id-event-123';
        });
    }

    public function testUniqueLockKeyUsesListenerClassName()
    {
        $listener = new CallQueuedListener(TestDispatcherShouldBeUnique::class, 'handle', []);
        $listener->shouldBeUnique = true;
        $listener->uniqueId = 'test-id';

        $this->assertSame(TestDispatcherShouldBeUnique::class, $listener->displayName());
        $this->assertSame(
            'laravel_unique_job:'.TestDispatcherShouldBeUnique::class.':test-id',
            \Illuminate\Bus\UniqueLock::getKey($listener)
        );
    }

    public function testUniqueLockIsAcquiredWithListenerClassName()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $cache = m::mock(Cache::class);
        $lock = m::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $expectedKey = 'laravel_unique_job:'.TestDispatcherShouldBeUnique::class.':unique-listener-id';

        $cache->shouldReceive('lock')
            ->once()
            ->with($expectedKey, 60)
            ->andReturn($lock);
        $lock->shouldReceive('get')->once()->andReturn(true);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherShouldBeUnique::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class);
    }

    public function testUniqueViaUsesListenerCacheRepository()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $defaultCache = m::mock(Cache::class);
        $uniqueCache = m::mock(Cache::class);
        $lock = m::mock(Lock::class);

        $container->instance(Cache::class, $defaultCache);

        $defaultCache->shouldNotReceive('lock');

        TestDispatcherShouldBeUniqueWithCustomCache::$cache = $uniqueCache;

        $expectedKey = 'laravel_unique_job:'.TestDispatcherShouldBeUniqueWithCustomCache::class.':unique-listener-id';

        $uniqueCache->shouldReceive('lock')
            ->once()
            ->with($expectedKey, 60)
            ->andReturn($lock);
        $lock->shouldReceive('get')->once()->andReturn(true);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherShouldBeUniqueWithCustomCache::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->assertPushed(CallQueuedListener::class);
    }

    public function testUniqueLockIsReleasedOnProcessingWithListenerClassName()
    {
        $container = new Container;
        $cache = m::mock(Cache::class);
        $lock = m::mock(Lock::class);

        $container->instance(Cache::class, $cache);
        $container->instance(BusDispatcher::class, new BusDispatcher($container));

        $listener = new CallQueuedListener(TestDispatcherShouldBeUnique::class, 'handle', ['foo', 'bar']);
        $listener->shouldBeUnique = true;
        $listener->uniqueId = 'unique-listener-id';
        $listener->uniqueFor = 60;

        $expectedKey = 'laravel_unique_job:'.TestDispatcherShouldBeUnique::class.':unique-listener-id';

        $cache->shouldReceive('lock')
            ->once()
            ->with($expectedKey)
            ->andReturn($lock);
        $lock->shouldReceive('forceRelease')->once();

        $job = m::mock(Job::class);
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();

        $handler = new CallQueuedHandler(new BusDispatcher($container), $container);
        $handler->call($job, ['command' => serialize($listener)]);
    }

    public function testUniqueUntilProcessingLockIsReleasedBeforeHandling()
    {
        $container = new Container;
        $cache = m::mock(Cache::class);
        $lock = m::mock(Lock::class);

        $container->instance(Cache::class, $cache);
        $container->instance(BusDispatcher::class, new BusDispatcher($container));

        TestDispatcherShouldBeUniqueUntilProcessing::$lockReleasedBeforeHandling = null;
        TestDispatcherShouldBeUniqueUntilProcessing::$cache = $cache;
        TestDispatcherShouldBeUniqueUntilProcessing::$expectedLockKey = 'laravel_unique_job:'.TestDispatcherShouldBeUniqueUntilProcessing::class.':until-processing-id';

        $listener = new CallQueuedListener(TestDispatcherShouldBeUniqueUntilProcessing::class, 'handle', ['foo', 'bar']);
        $listener->shouldBeUnique = true;
        $listener->shouldBeUniqueUntilProcessing = true;
        $listener->uniqueId = 'until-processing-id';

        $expectedKey = 'laravel_unique_job:'.TestDispatcherShouldBeUniqueUntilProcessing::class.':until-processing-id';

        $cache->shouldReceive('lock')
            ->with($expectedKey)
            ->andReturn($lock);
        $lock->shouldReceive('forceRelease')->once();

        $job = m::mock(Job::class);
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();

        $handler = new CallQueuedHandler(new BusDispatcher($container), $container);
        $handler->call($job, ['command' => serialize($listener)]);

        $this->assertTrue(TestDispatcherShouldBeUniqueUntilProcessing::$lockReleasedBeforeHandling);
    }
}

class TestDispatcherQueuedHandler implements ShouldQueue
{
    public function handle()
    {
        //
    }
}

class TestDispatcherConnectionQueuedHandler implements ShouldQueue
{
    public $connection = 'redis';

    public $delay = 10;

    public $queue = 'my_queue';

    public function handle()
    {
        //
    }
}

class TestDispatcherGetQueue implements ShouldQueue
{
    public $queue = 'my_queue';

    public function handle()
    {
        //
    }

    public function viaQueue()
    {
        return 'some_other_queue';
    }
}

class TestDispatcherGetConnection implements ShouldQueue
{
    public $connection = 'my_connection';

    public function handle()
    {
        //
    }

    public function viaConnection()
    {
        return 'some_other_connection';
    }
}

class TestDispatcherGetDelay implements ShouldQueue
{
    public $delay = 10;

    public function handle()
    {
        //
    }

    public function withDelay()
    {
        return 20;
    }
}

class TestDispatcherOptions implements ShouldQueue
{
    public $maxExceptions = 1;

    public function retryUntil()
    {
        return now()->addHour(1);
    }

    public function tries()
    {
        return 5;
    }

    public function handle()
    {
        //
    }
}

class TestDispatcherWithMessageGroupProperty implements ShouldQueue
{
    public $messageGroup = 'group-property';

    public function handle()
    {
        //
    }
}

class TestDispatcherWithMessageGroupMethod implements ShouldQueue
{
    public $messageGroup = 'group-property';

    public function handle()
    {
        //
    }

    public function messageGroup($event)
    {
        return 'group-method';
    }
}

class TestDispatcherWithDeduplicationIdMethod implements ShouldQueue
{
    public function handle()
    {
        //
    }

    public function deduplicationId($payload, $queue)
    {
        return 'deduplication-id-method';
    }
}

class TestDispatcherWithDeduplicatorMethod implements ShouldQueue
{
    public function handle()
    {
        //
    }

    public function deduplicationId($payload, $queue)
    {
        return 'deduplication-id-method';
    }

    public function deduplicator($event)
    {
        return fn ($payload, $queue) => 'deduplicator-method';
    }
}

class TestDispatcherMiddleware implements ShouldQueue
{
    public function middleware($a, $b)
    {
        return [new TestMiddleware($a, $b)];
    }

    public function handle($a, $b)
    {
        //
    }
}

class TestMiddleware
{
    public $a;
    public $b;

    public function __construct($a, $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function handle($job, $next)
    {
        $next($job);
    }
}

class TestDispatcherGetConnectionDynamically implements ShouldQueue
{
    public function handle()
    {
        //
    }

    public function viaConnection($event)
    {
        if ($event['shouldUseRedisConnection']) {
            return 'redis';
        }

        return 'sqs';
    }
}

class TestDispatcherGetQueueDynamically implements ShouldQueue
{
    public $queue = 'my_queue';

    public function handle()
    {
        //
    }

    public function viaQueue($event)
    {
        if ($event['useHighPriorityQueue']) {
            return 'p0';
        }

        return 'p99';
    }
}

class TestDispatcherGetDelayDynamically implements ShouldQueue
{
    public $delay = 10;

    public function handle()
    {
        //
    }

    public function withDelay($event)
    {
        if ($event['useHighDelay']) {
            return 60;
        }

        return 20;
    }
}

enum TestQueueType: string
{
    case EnumeratedQueue = 'enumerated-queue';
}

class TestDispatcherViaQueueSupportsEnum implements ShouldQueue
{
    public function viaQueue()
    {
        return TestQueueType::EnumeratedQueue;
    }
}

class TestDispatcherQueueRoutes implements ShouldQueue
{
    public function handle()
    {
        //
    }
}

class TestDispatcherShouldBeUnique implements ShouldQueue, ShouldBeUnique
{
    public $uniqueId = 'unique-listener-id';

    public $uniqueFor = 60;

    public function handle()
    {
        //
    }
}

class TestDispatcherShouldBeUniqueUntilProcessing implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use InteractsWithQueue;

    public static $lockReleasedBeforeHandling = null;
    public static $cache = null;
    public static $expectedLockKey = '';

    public function handle()
    {
        $lock = m::mock(Lock::class);
        $lock->shouldReceive('get')->andReturn(true);
        static::$cache->shouldReceive('lock')
            ->with(static::$expectedLockKey, 10)
            ->andReturn($lock);

        static::$lockReleasedBeforeHandling = static::$cache->lock(static::$expectedLockKey, 10)->get();
    }
}

class TestDispatcherUniqueIdFromMethod implements ShouldQueue, ShouldBeUnique
{
    public function handle()
    {
        //
    }

    public function uniqueId($event)
    {
        return 'unique-id-'.$event['id'];
    }
}

class TestDispatcherShouldBeUniqueWithCustomCache implements ShouldQueue, ShouldBeUnique
{
    public static $cache = null;

    public function handle()
    {
        //
    }

    public function uniqueId()
    {
        return 'unique-listener-id';
    }

    public function uniqueFor()
    {
        return 60;
    }

    public function uniqueVia(): Cache
    {
        return static::$cache;
    }
}
