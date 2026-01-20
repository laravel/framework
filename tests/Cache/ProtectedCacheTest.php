<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Events\CacheFlushed;
use Illuminate\Cache\Events\CacheFlushing;
use Illuminate\Cache\ProtectedCache;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ProtectedCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        m::close();

        parent::tearDown();
    }

    public function testProtectedMethodReturnsProtectedCacheInstance()
    {
        $repo = $this->getRepository();
        $protected = $repo->protected();

        $this->assertInstanceOf(ProtectedCache::class, $protected);
    }

    public function testProtectedCacheUsesCorrectKeyPrefix()
    {
        $this->assertSame('__protected__:', ProtectedCache::PREFIX);
    }

    public function testProtectedCacheItemsCanBeSetAndRetrieved()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        $repo->protected()->put('foo', 'bar', 3600);
        $this->assertSame('bar', $repo->protected()->get('foo'));
    }

    public function testFlushClearsEverythingIncludingProtected()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        // Store regular and protected items
        $repo->put('regular', 'value1', 3600);
        $repo->protected()->put('protected', 'value2', 3600);

        // flush() clears EVERYTHING (original behavior)
        $store->flush();

        $this->assertNull($repo->get('regular'));
        $this->assertNull($repo->protected()->get('protected'));
    }

    public function testFlushUnprotectedPreservesProtectedItems()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        // Store regular and protected items
        $repo->put('regular', 'value1', 3600);
        $repo->protected()->put('protected', 'value2', 3600);

        // flushUnprotected() preserves protected items
        $store->flushUnprotected();

        // Verify protected item survives, regular is gone
        $this->assertNull($repo->get('regular'));
        $this->assertSame('value2', $repo->protected()->get('protected'));
    }

    public function testProtectedForever()
    {
        Carbon::setTestNow(Carbon::now());

        $store = new ArrayStore;
        $repo = new Repository($store);

        $repo->protected()->forever('foo', 'bar');
        $this->assertSame('bar', $repo->protected()->get('foo'));

        // Even after a long time
        Carbon::setTestNow(Carbon::now()->addYears(10));
        $this->assertSame('bar', $repo->protected()->get('foo'));
    }

    public function testProtectedRemember()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        // First call should execute callback
        $result = $repo->protected()->remember('key', 3600, function () {
            return 'expensive value';
        });
        $this->assertSame('expensive value', $result);

        // Second call should return cached value
        $callbackCalled = false;
        $result = $repo->protected()->remember('key', 3600, function () use (&$callbackCalled) {
            $callbackCalled = true;

            return 'new value';
        });

        $this->assertSame('expensive value', $result);
        $this->assertFalse($callbackCalled);
    }

    public function testProtectedPull()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        $repo->protected()->put('foo', 'bar', 3600);
        $value = $repo->protected()->pull('foo');

        $this->assertSame('bar', $value);
        $this->assertNull($repo->protected()->get('foo'));
    }

    public function testProtectedForget()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        $repo->protected()->put('foo', 'bar', 3600);
        $this->assertSame('bar', $repo->protected()->get('foo'));

        $repo->protected()->forget('foo');
        $this->assertNull($repo->protected()->get('foo'));
    }

    public function testProtectedHas()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        $this->assertFalse($repo->protected()->has('foo'));

        $repo->protected()->put('foo', 'bar', 3600);
        $this->assertTrue($repo->protected()->has('foo'));
    }

    public function testProtectedCacheInheritsEventDispatcher()
    {
        $repo = $this->getRepository();
        $protected = $repo->protected();

        $this->assertSame($repo->getEventDispatcher(), $protected->getEventDispatcher());
    }

    public function testProtectedCacheInheritsDefaultCacheTime()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);
        $repo->setDefaultCacheTime(7200);

        $protected = $repo->protected();

        $this->assertSame(7200, $protected->getDefaultCacheTime());
    }

    public function testRepositoryFlushUnprotectedFiresEvents()
    {
        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(CacheFlushing::class));
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(CacheFlushed::class));

        $store = new ArrayStore;
        $repo = new Repository($store);
        $repo->setEventDispatcher($dispatcher);

        $repo->flushUnprotected();
    }

    public function testRepositoryFlushUnprotectedPreservesProtected()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        // Store regular and protected items
        $repo->put('regular', 'value1', 3600);
        $repo->protected()->put('protected', 'value2', 3600);

        // Use repository flushUnprotected
        $repo->flushUnprotected();

        // Regular should be gone, protected should survive
        $this->assertNull($repo->get('regular'));
        $this->assertSame('value2', $repo->protected()->get('protected'));
    }

    public function testMultipleProtectedItemsSurviveFlushUnprotected()
    {
        $store = new ArrayStore;
        $repo = new Repository($store);

        // Store multiple protected and regular items
        $repo->put('regular1', 'r1', 3600);
        $repo->put('regular2', 'r2', 3600);
        $repo->protected()->put('protected1', 'p1', 3600);
        $repo->protected()->put('protected2', 'p2', 3600);

        // FlushUnprotected
        $store->flushUnprotected();

        // All regular items should be gone
        $this->assertNull($repo->get('regular1'));
        $this->assertNull($repo->get('regular2'));

        // All protected items should survive
        $this->assertSame('p1', $repo->protected()->get('protected1'));
        $this->assertSame('p2', $repo->protected()->get('protected2'));
    }

    public function testFlushUnprotectedDelegatesToStoreFlushUnprotectedMethod()
    {
        $store = m::mock(ArrayStore::class);
        $store->shouldReceive('flushUnprotected')->once()->andReturn(true);

        $repo = new Repository($store);
        $result = $repo->flushUnprotected();

        $this->assertTrue($result);
    }

    public function testFlushUnprotectedFallsBackToFlushWhenFlushUnprotectedNotAvailable()
    {
        $store = m::mock(Store::class);
        $store->shouldReceive('flush')->once()->andReturn(true);

        $repo = new Repository($store);
        $result = $repo->flushUnprotected();

        $this->assertTrue($result);
    }

    protected function getRepository()
    {
        $dispatcher = new Dispatcher(m::mock(Container::class));
        $repository = new Repository(new ArrayStore);

        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }
}
