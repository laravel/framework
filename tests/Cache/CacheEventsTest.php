<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Events\CacheFlushed;
use Illuminate\Cache\Events\CacheFlushFailed;
use Illuminate\Cache\Events\CacheFlushing;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheLocksFlushed;
use Illuminate\Cache\Events\CacheLocksFlushFailed;
use Illuminate\Cache\Events\CacheLocksFlushing;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\ForgettingKey;
use Illuminate\Cache\Events\KeyForgetFailed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Cache\Events\RetrievingManyKeys;
use Illuminate\Cache\Events\WritingKey;
use Illuminate\Cache\Events\WritingManyKeys;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Events\Dispatcher;
use Mockery;
use PHPUnit\Framework\TestCase;

class CacheEventsTest extends TestCase
{
    public function testHasTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertFalse($repository->has('foo'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']);

        $this->assertTrue($repository->has('baz'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz']);
        $this->assertEventDispatched(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux']);

        $this->assertFalse($repository->tags('taylor')->has('foo'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]);

        $this->assertTrue($repository->tags('taylor')->has('baz'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]);
        $this->assertEventDispatched(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]);
    }

    public function testGetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertNull($repository->get('foo'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']);

        $this->assertSame(['foo' => null, 'bar' => null], $repository->get(['foo', 'bar']));
        $this->assertEventDispatched(RetrievingManyKeys::class, ['storeName' => 'array', 'keys' => ['foo', 'bar']]);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'bar']);

        $this->assertSame('qux', $repository->get('baz'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz']);
        $this->assertEventDispatched(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux']);

        $this->assertNull($repository->tags('taylor')->get('foo'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]);

        $this->assertSame('qux', $repository->tags('taylor')->get('baz'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]);
        $this->assertEventDispatched(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]);
    }

    public function testPullTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertSame('qux', $repository->pull('baz'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz']);
        $this->assertEventDispatched(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux']);
        $this->assertEventDispatched(ForgettingKey::class, ['storeName' => 'array', 'key' => 'baz']);
        $this->assertEventDispatched(KeyForgotten::class, ['storeName' => 'array', 'key' => 'baz']);
    }

    public function testPullTriggersEventsUsingTags()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertSame('qux', $repository->tags('taylor')->pull('baz'));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]);
        $this->assertEventDispatched(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]);
        $this->assertEventDispatched(ForgettingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]);
        $this->assertEventDispatched(KeyForgotten::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]);
    }

    public function testPutTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $repository->put('foo', 'bar', 99);
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]);

        $repository->putMany(['foo' => 'bar', 'baz' => 'qux'], 99);
        $this->assertEventDispatched(WritingManyKeys::class, ['storeName' => 'array', 'keys' => ['foo', 'baz'], 'values' => ['bar', 'qux'], 'seconds' => 99]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux', 'seconds' => 99]);

        $repository->tags('taylor')->put('foo', 'bar', 99);
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]);
    }

    public function testAddTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertTrue($repository->add('foo', 'bar', 99));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]);

        $this->assertTrue($repository->tags('taylor')->add('foo', 'bar', 99));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]);
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]);
    }

    public function testForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $repository->forever('foo', 'bar');
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null]);

        $repository->tags('taylor')->forever('foo', 'bar');
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null, 'tags' => ['taylor']]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null, 'tags' => ['taylor']]);
    }

    public function testRememberTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertSame('bar', $repository->remember('foo', 99, function () {
            return 'bar';
        }));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]);

        $this->assertSame('bar', $repository->tags('taylor')->remember('foo', 99, function () {
            return 'bar';
        }));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]);
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]);
    }

    public function testRememberForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertSame('bar', $repository->rememberForever('foo', function () {
            return 'bar';
        }));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null]);

        $this->assertSame('bar', $repository->tags('taylor')->rememberForever('foo', function () {
            return 'bar';
        }));
        $this->assertEventDispatched(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']);
        $this->assertEventDispatched(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]);
        $this->assertEventDispatched(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null, 'tags' => ['taylor']]);
        $this->assertEventDispatched(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null, 'tags' => ['taylor']]);
    }

    public function testForgetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertTrue($repository->forget('baz'));
        $this->assertEventDispatched(ForgettingKey::class, ['storeName' => 'array', 'key' => 'baz']);
        $this->assertEventDispatched(KeyForgotten::class, ['storeName' => 'array', 'key' => 'baz']);

        $this->assertTrue($repository->tags('taylor')->forget('baz'));
        $this->assertEventDispatched(ForgettingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]);
        $this->assertEventDispatched(KeyForgotten::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]);
    }

    public function testForgetDoesTriggerFailedEventOnFailure()
    {
        $dispatcher = $this->getDispatcher();
        $store = Mockery::mock(Store::class);
        $store->expects('forget')->andReturn(false);
        $repository = new Repository($store);
        $repository->setEventDispatcher($dispatcher);

        $this->assertFalse($repository->forget('baz'));
        $this->assertEventDispatched(ForgettingKey::class, ['key' => 'baz']);
        $this->assertEventDispatched(KeyForgetFailed::class, ['key' => 'baz']);
    }

    public function testFlushTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertTrue($repository->clear());
        $this->assertEventDispatched(CacheFlushing::class, ['storeName' => 'array']);
        $this->assertEventDispatched(CacheFlushed::class, ['storeName' => 'array']);
    }

    public function testFlushLocksTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $this->assertTrue($repository->flushLocks());
        $this->assertEventDispatched(CacheLocksFlushing::class, ['storeName' => 'array']);
        $this->assertEventDispatched(CacheLocksFlushed::class, ['storeName' => 'array']);
    }

    public function testFlushFailureDoesDispatchEvent()
    {
        $dispatcher = $this->getDispatcher();

        // Create a store that fails to flush
        $failingStore = Mockery::mock(Store::class);
        $failingStore->expects('flush')->andReturn(false);

        $repository = new Repository($failingStore, ['store' => 'array']);
        $repository->setEventDispatcher($dispatcher);

        $this->assertFalse($repository->clear());
        $this->assertEventDispatched(CacheFlushing::class, ['storeName' => 'array']);
        $this->assertEventDispatched(CacheFlushFailed::class, ['storeName' => 'array']);
    }

    public function testFlushLocksFailureDoesDispatchEvent()
    {
        $dispatcher = $this->getDispatcher();

        // Create a store that fails to flush locks
        $failingStore = Mockery::mock(ArrayStore::class);
        $failingStore->expects('flushLocks')->andReturn(false);

        $repository = new Repository($failingStore, ['store' => 'array']);
        $repository->setEventDispatcher($dispatcher);

        $this->assertFalse($repository->flushLocks());
        $this->assertEventDispatched(CacheLocksFlushing::class, ['storeName' => 'array']);
        $this->assertEventDispatched(CacheLocksFlushFailed::class, ['storeName' => 'array']);
    }

    protected $dispatchedEvents = [];

    protected function assertEventDispatched($eventClass, $properties = [])
    {
        $event = array_shift($this->dispatchedEvents);

        $this->assertInstanceOf($eventClass, $event);

        foreach ($properties as $name => $value) {
            $this->assertEquals($value, $event->$name, "Event property [{$name}] does not match.");
        }
    }

    protected function getDispatcher()
    {
        $this->dispatchedEvents = [];

        $dispatcher = new Dispatcher;
        $dispatcher->listen('*', function ($event, $payload) {
            $this->dispatchedEvents[] = $payload[0];
        });

        return $dispatcher;
    }

    protected function getRepository($dispatcher)
    {
        $repository = new Repository(new ArrayStore, ['store' => 'array']);
        $repository->put('baz', 'qux', 99);
        $repository->tags('taylor')->put('baz', 'qux', 99);
        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }
}
