<?php

namespace Illuminate\Tests\Events;

use Illuminate\Bus\DebounceLock;
use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Attributes\DebounceFor;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Laravel\SerializableClosure\SerializableClosure;
use LogicException;
use Mockery;
use PHPUnit\Framework\TestCase;

class QueuedEventsTest extends TestCase
{
    public function testQueuedEventHandlersAreQueued()
    {
        $d = new Dispatcher;
        $queue = Mockery::mock(Queue::class);

        $queue->expects('connection')->with(null)->andReturnSelf();

        $queue->expects('pushOn')->with(null, Mockery::type(CallQueuedListener::class));

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
        $queue = Mockery::mock(Queue::class);

        $queue->expects('connection')->with('some_other_connection')->andReturnSelf();

        $queue->expects('pushOn')->with(null, Mockery::type(CallQueuedListener::class));

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', TestDispatcherGetConnection::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);
    }

    public function testDelayIsSetByWithDelay()
    {
        $d = new Dispatcher;
        $queue = Mockery::mock(Queue::class);

        $queue->expects('connection')->with(null)->andReturnSelf();

        $queue->expects('laterOn')->with(null, 20, Mockery::type(CallQueuedListener::class));

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
    }

    public function testConnectionIsSetUsingForwardedQueue()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $queueRoutes = new QueueRoutes;
        $queueRoutes->forward('reports', 'processing', 'cloud');
        $container->instance('queue.routes', $queueRoutes);

        $fakeQueue = new QueueFake($container);

        Container::setInstance($container);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherForwardedQueue::class.'@handle');
        $d->dispatch('some.event', ['foo', 'bar']);

        $fakeQueue->connection('cloud')->assertPushedOn('reports', CallQueuedListener::class);
    }

    public function testDelayIsSetByWithDelayDynamically()
    {
        $d = new Dispatcher;
        $queue = Mockery::mock(Queue::class);

        $queue->expects('connection')->with(null)->andReturnSelf();

        $queue->expects('laterOn')->with(null, 60, Mockery::type(CallQueuedListener::class));

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
        $queue = Mockery::mock(Queue::class);

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
        $cache = Mockery::mock(Cache::class);
        $lock = Mockery::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $cache->expects('lock')->andReturn($lock);
        $cache->expects('getStore')->andReturn(Mockery::mock(LockProvider::class));
        $lock->expects('get')->andReturn(true);
        $lock->expects('owner')->andReturn('unique-lock-owner');

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
        $cache = Mockery::mock(Cache::class);
        $lock = Mockery::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $cache->expects('lock')->andReturn($lock);
        $lock->expects('get')->andReturn(false);

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
        $cache = Mockery::mock(Cache::class);
        $lock = Mockery::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $cache->expects('lock')->andReturn($lock);
        $cache->expects('getStore')->andReturn(Mockery::mock(LockProvider::class));
        $lock->expects('get')->andReturn(true);
        $lock->expects('owner')->andReturn('unique-lock-owner');

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
        $cache = Mockery::mock(Cache::class);
        $lock = Mockery::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $cache->expects('lock')->andReturn($lock);
        $cache->expects('getStore')->andReturn(Mockery::mock(LockProvider::class));
        $lock->expects('get')->andReturn(true);
        $lock->expects('owner')->andReturn('unique-lock-owner');

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
            'laravel_unique_job:'.hash('xxh128', TestDispatcherShouldBeUnique::class).':test-id',
            \Illuminate\Bus\UniqueLock::getKey($listener)
        );
    }

    public function testUniqueLockIsAcquiredWithListenerClassName()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $cache = Mockery::mock(Cache::class);
        $lock = Mockery::mock(Lock::class);

        $container->instance(Cache::class, $cache);

        $expectedKey = 'laravel_unique_job:'.hash('xxh128', TestDispatcherShouldBeUnique::class).':unique-listener-id';

        $cache->expects('lock')
            ->with($expectedKey, 60)
            ->andReturn($lock);
        $cache->expects('getStore')->andReturn(Mockery::mock(LockProvider::class));
        $lock->expects('get')->andReturn(true);
        $lock->expects('owner')->andReturn('unique-lock-owner');

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
        $defaultCache = Mockery::mock(Cache::class);
        $uniqueCache = Mockery::mock(Cache::class);
        $lock = Mockery::mock(Lock::class);

        $container->instance(Cache::class, $defaultCache);

        $defaultCache->shouldNotReceive('lock');

        TestDispatcherShouldBeUniqueWithCustomCache::$cache = $uniqueCache;

        $expectedKey = 'laravel_unique_job:'.hash('xxh128', TestDispatcherShouldBeUniqueWithCustomCache::class).':unique-listener-id';

        $uniqueCache->expects('lock')
            ->with($expectedKey, 60)
            ->andReturn($lock);
        $uniqueCache->expects('getStore')->andReturn(Mockery::mock(LockProvider::class));
        $lock->expects('get')->andReturn(true);
        $lock->expects('owner')->andReturn('unique-lock-owner');

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
        $cache = Mockery::mock(Cache::class);
        $lock = Mockery::mock(Lock::class);

        $container->instance(Cache::class, $cache);
        $container->instance(BusDispatcher::class, new BusDispatcher($container));

        $listener = new CallQueuedListener(TestDispatcherShouldBeUnique::class, 'handle', ['foo', 'bar']);
        $listener->shouldBeUnique = true;
        $listener->uniqueId = 'unique-listener-id';
        $listener->uniqueFor = 60;

        $expectedKey = 'laravel_unique_job:'.hash('xxh128', TestDispatcherShouldBeUnique::class).':unique-listener-id';

        $cache->expects('lock')
            ->with($expectedKey)
            ->andReturn($lock);
        $lock->expects('forceRelease');

        $job = Mockery::mock(Job::class);
        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(false);
        $job->expects('isDeletedOrReleased')->andReturn(false);
        $job->expects('delete');

        $handler = new CallQueuedHandler(new BusDispatcher($container), $container);
        $handler->call($job, ['command' => serialize($listener)]);
    }

    public function testUniqueUntilProcessingLockIsReleasedBeforeHandling()
    {
        $container = new Container;
        $cache = Mockery::mock(Cache::class);
        $lock = Mockery::mock(Lock::class);

        $container->instance(Cache::class, $cache);
        $container->instance(BusDispatcher::class, new BusDispatcher($container));

        TestDispatcherShouldBeUniqueUntilProcessing::$lockReleasedBeforeHandling = null;
        TestDispatcherShouldBeUniqueUntilProcessing::$cache = $cache;
        TestDispatcherShouldBeUniqueUntilProcessing::$expectedLockKey = 'laravel_unique_job:'.hash('xxh128', TestDispatcherShouldBeUniqueUntilProcessing::class).':until-processing-id';

        $listener = new CallQueuedListener(TestDispatcherShouldBeUniqueUntilProcessing::class, 'handle', ['foo', 'bar']);
        $listener->shouldBeUnique = true;
        $listener->shouldBeUniqueUntilProcessing = true;
        $listener->uniqueId = 'until-processing-id';

        $expectedKey = 'laravel_unique_job:'.hash('xxh128', TestDispatcherShouldBeUniqueUntilProcessing::class).':until-processing-id';

        $cache->expects('lock')
            ->with($expectedKey)
            ->andReturn($lock);
        $lock->expects('forceRelease');

        $job = Mockery::mock(Job::class);
        $job->expects('hasFailed')->andReturn(false);
        $job->expects('isReleased')->times(2)->andReturn(false);
        $job->expects('isDeletedOrReleased')->andReturn(false);
        $job->expects('attempts')->andReturn(1);
        $job->expects('delete');

        $handler = new CallQueuedHandler(new BusDispatcher($container), $container);
        $handler->call($job, ['command' => serialize($listener)]);

        $this->assertTrue(TestDispatcherShouldBeUniqueUntilProcessing::$lockReleasedBeforeHandling);
    }

    public function testQueuePropagatesDebounceOptions()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $cache = new Repository(new ArrayStore);

        $container->instance(Cache::class, $cache);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherDebouncedHandler::class.'@handle');
        $d->dispatch('some.event', [['id' => 'event-123'], 'bar']);

        $expectedKey = 'laravel_debounced_job:'.hash('xxh128', TestDispatcherDebouncedHandler::class).':event-123';

        $fakeQueue->assertPushed(CallQueuedListener::class, function ($job) use ($cache, $expectedKey) {
            return $job->debounceId() === 'event-123'
                && is_string($job->debounceOwner)
                && $job->debounceOwner !== ''
                && $cache->get($expectedKey) === $job->debounceOwner;
        });

        $this->assertSame(1, $fakeQueue->delayedSize());
    }

    public function testExplicitListenerDelayTakesPrecedenceOverDebounceDelay()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $queue = Mockery::mock(Queue::class);
        $cache = new Repository(new ArrayStore);

        $container->instance(Cache::class, $cache);

        $queue->expects('connection')->with(null)->andReturnSelf();
        $queue->expects('laterOn')->with(null, 20, Mockery::on(function ($job) use ($cache) {
            $expectedKey = 'laravel_debounced_job:'.hash('xxh128', TestDispatcherDebouncedHandlerWithDelay::class).':event-123';

            return $job instanceof CallQueuedListener
                && $job->debounceOwner !== ''
                && $cache->get($expectedKey) === $job->debounceOwner;
        }));

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', TestDispatcherDebouncedHandlerWithDelay::class.'@handle');
        $d->dispatch('some.event', [['id' => 'event-123'], 'bar']);
    }

    public function testDebouncedListenerMaxWaitForcesImmediateExecution()
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $container = new Container;
        $d = new Dispatcher($container);

        $queue = Mockery::mock(Queue::class);
        $cache = new Repository(new ArrayStore);

        $container->instance(Cache::class, $cache);

        $queue->expects('connection')->twice()->with(null)->andReturnSelf();
        $queue->expects('laterOn')->with(null, 30, Mockery::type(CallQueuedListener::class))->ordered();
        $queue->expects('laterOn')->with(null, 0, Mockery::type(CallQueuedListener::class))->ordered();

        $d->setQueueResolver(function () use ($queue) {
            return $queue;
        });

        $d->listen('some.event', TestDispatcherDebouncedHandlerWithMaxWait::class.'@handle');
        $d->dispatch('some.event', [['id' => 'event-123'], 'bar']);

        Carbon::setTestNow(Carbon::now()->addSeconds(60));

        $d->dispatch('some.event', [['id' => 'event-123'], 'bar']);
    }

    public function testDebounceViaUsesListenerCacheRepository()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $defaultCache = new Repository(new ArrayStore);
        $debounceCache = new Repository(new ArrayStore);

        $container->instance(Cache::class, $defaultCache);

        $originalContainer = Container::getInstance();
        Container::setInstance($container);

        TestDispatcherDebouncedHandlerWithCustomCache::$cache = ['event-123' => $debounceCache];

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        try {
            $d->listen('some.event', TestDispatcherDebouncedHandlerWithCustomCache::class.'@handle');
            $d->dispatch('some.event', [['id' => 'event-123'], 'bar']);

            $job = $fakeQueue->pushed(CallQueuedListener::class)->first();

            $this->assertNull($defaultCache->get(DebounceLock::getKey($job)));
            $this->assertSame($job->debounceOwner, $debounceCache->get(DebounceLock::getKey($job)));
        } finally {
            Container::setInstance($originalContainer);
        }
    }

    public function testDebouncedListenerCannotAlsoBeUnique()
    {
        $container = new Container;
        $d = new Dispatcher($container);

        $fakeQueue = new QueueFake($container);
        $cache = Mockery::mock(Cache::class);

        $cache->shouldNotReceive('put');
        $cache->shouldNotReceive('lock');

        $container->instance(Cache::class, $cache);

        $d->setQueueResolver(function () use ($fakeQueue) {
            return $fakeQueue;
        });

        $d->listen('some.event', TestDispatcherDebouncedAndUniqueHandler::class.'@handle');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A debounced listener cannot also implement ShouldBeUnique.');

        $d->dispatch('some.event', [['id' => 'event-123'], 'bar']);
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
        return Carbon::now()->addHour();
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

class TestDispatcherForwardedQueue implements ShouldQueue
{
    public $queue = 'reports';

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
        $lock = Mockery::mock(Lock::class);
        $lock->expects('get')->andReturn(true);
        static::$cache->expects('lock')
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

#[DebounceFor(30)]
class TestDispatcherDebouncedHandler implements ShouldQueue
{
    public function debounceId($event)
    {
        return $event['id'];
    }

    public function handle()
    {
        //
    }
}

#[DebounceFor(30)]
class TestDispatcherDebouncedHandlerWithDelay implements ShouldQueue
{
    public $debounceId = 'event-123';

    public function withDelay()
    {
        return 20;
    }

    public function handle()
    {
        //
    }
}

#[DebounceFor(30, maxWait: 60)]
class TestDispatcherDebouncedHandlerWithMaxWait implements ShouldQueue
{
    public function debounceId($event)
    {
        return $event['id'];
    }

    public function handle()
    {
        //
    }
}

#[DebounceFor(30)]
class TestDispatcherDebouncedHandlerWithCustomCache implements ShouldQueue
{
    public static $cache = null;

    public function debounceId($event)
    {
        return $event['id'];
    }

    public function debounceVia($event): Cache
    {
        return static::$cache[$event['id']];
    }

    public function handle()
    {
        //
    }
}

#[DebounceFor(30)]
class TestDispatcherDebouncedAndUniqueHandler implements ShouldQueue, ShouldBeUnique
{
    public function debounceId($event)
    {
        return $event['id'];
    }

    public function handle()
    {
        //
    }
}
