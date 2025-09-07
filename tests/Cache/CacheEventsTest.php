<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Events\CacheFlushed;
use Illuminate\Cache\Events\CacheFlushFailed;
use Illuminate\Cache\Events\CacheFlushing;
use Illuminate\Cache\Events\CacheHit;
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
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheEventsTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testHasTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']));
        $this->assertFalse($repository->has('foo'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux']));
        $this->assertTrue($repository->has('baz'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]));
        $this->assertFalse($repository->tags('taylor')->has('foo'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]));
        $this->assertTrue($repository->tags('taylor')->has('baz'));
    }

    public function testGetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']));
        $this->assertNull($repository->get('foo'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingManyKeys::class, ['storeName' => 'array', 'keys' => ['foo', 'bar']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'bar']));
        $this->assertSame(['foo' => null, 'bar' => null], $repository->get(['foo', 'bar']));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux']));
        $this->assertSame('qux', $repository->get('baz'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]));
        $this->assertNull($repository->tags('taylor')->get('foo'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]));
        $this->assertSame('qux', $repository->tags('taylor')->get('baz'));
    }

    public function testPullTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(ForgettingKey::class, ['storeName' => 'array', 'key' => 'baz']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgotten::class, ['storeName' => 'array', 'key' => 'baz']));
        $this->assertSame('qux', $repository->pull('baz'));
    }

    public function testPullTriggersEventsUsingTags()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheHit::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(ForgettingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgotten::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]));
        $this->assertSame('qux', $repository->tags('taylor')->pull('baz'));
    }

    public function testPutTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]));
        $repository->put('foo', 'bar', 99);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingManyKeys::class, ['storeName' => 'array', 'keys' => ['foo', 'baz'], 'values' => ['bar', 'qux'], 'seconds' => 99]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'baz', 'value' => 'qux', 'seconds' => 99]));
        $repository->putMany(['foo' => 'bar', 'baz' => 'qux'], 99);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]));
        $repository->tags('taylor')->put('foo', 'bar', 99);
    }

    public function testAddTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]));
        $this->assertTrue($repository->add('foo', 'bar', 99));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]));
        $this->assertTrue($repository->tags('taylor')->add('foo', 'bar', 99));
    }

    public function testForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null]));
        $repository->forever('foo', 'bar');

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null, 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null, 'tags' => ['taylor']]));
        $repository->tags('taylor')->forever('foo', 'bar');
    }

    public function testRememberTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99]));
        $this->assertSame('bar', $repository->remember('foo', 99, function () {
            return 'bar';
        }));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => 99, 'tags' => ['taylor']]));
        $this->assertSame('bar', $repository->tags('taylor')->remember('foo', 99, function () {
            return 'bar';
        }));
    }

    public function testRememberForeverTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null]));
        $this->assertSame('bar', $repository->rememberForever('foo', function () {
            return 'bar';
        }));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(RetrievingKey::class, ['storeName' => 'array', 'key' => 'foo']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(CacheMissed::class, ['storeName' => 'array', 'key' => 'foo', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(WritingKey::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null, 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyWritten::class, ['storeName' => 'array', 'key' => 'foo', 'value' => 'bar', 'seconds' => null, 'tags' => ['taylor']]));
        $this->assertSame('bar', $repository->tags('taylor')->rememberForever('foo', function () {
            return 'bar';
        }));
    }

    public function testForgetTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(ForgettingKey::class, ['storeName' => 'array', 'key' => 'baz']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgotten::class, ['storeName' => 'array', 'key' => 'baz']));
        $this->assertTrue($repository->forget('baz'));

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(ForgettingKey::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgotten::class, ['storeName' => 'array', 'key' => 'baz', 'tags' => ['taylor']]));
        $this->assertTrue($repository->tags('taylor')->forget('baz'));
    }

    public function testForgetDoesTriggerFailedEventOnFailure()
    {
        $dispatcher = $this->getDispatcher();
        $store = m::mock(Store::class);
        $store->shouldReceive('forget')->andReturn(false);
        $repository = new Repository($store);
        $repository->setEventDispatcher($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(ForgettingKey::class, ['key' => 'baz']));
        $dispatcher->shouldReceive('dispatch')->once()->with($this->assertEventMatches(KeyForgetFailed::class, ['key' => 'baz']));
        $this->assertFalse($repository->forget('baz'));
    }

    public function testFlushTriggersEvents()
    {
        $dispatcher = $this->getDispatcher();
        $repository = $this->getRepository($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with(
            $this->assertEventMatches(CacheFlushing::class, [
                'storeName' => 'array',
            ])
        );

        $dispatcher->shouldReceive('dispatch')->once()->with(
            $this->assertEventMatches(CacheFlushed::class, [
                'storeName' => 'array',
            ])
        );
        $this->assertTrue($repository->clear());
    }

    public function testFlushFailureDoesDispatchEvent()
    {
        $dispatcher = $this->getDispatcher();

        // Create a store that fails to flush
        $failingStore = m::mock(Store::class);
        $failingStore->shouldReceive('flush')->andReturn(false);

        $repository = new Repository($failingStore, ['store' => 'array']);
        $repository->setEventDispatcher($dispatcher);

        $dispatcher->shouldReceive('dispatch')->once()->with(
            $this->assertEventMatches(CacheFlushing::class, [
                'storeName' => 'array',
            ])
        );

        $dispatcher->shouldReceive('dispatch')->once()->with(
            $this->assertEventMatches(CacheFlushFailed::class, [
                'storeName' => 'array',
            ])
        );
        $this->assertFalse($repository->clear());
    }

    protected function assertEventMatches($eventClass, $properties = [])
    {
        return m::on(function ($event) use ($eventClass, $properties) {
            if (! $event instanceof $eventClass) {
                return false;
            }

            foreach ($properties as $name => $value) {
                if ($value != $event->$name) {
                    return false;
                }
            }

            return true;
        });
    }

    protected function getDispatcher()
    {
        return m::mock(Dispatcher::class);
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
