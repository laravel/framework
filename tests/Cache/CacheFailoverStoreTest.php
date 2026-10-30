<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\FailoverStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\CanFlushLocks;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Events\Dispatcher;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CacheFailoverStoreTest extends TestCase
{
    public function testImplementsCanFlushLocks()
    {
        $store = $this->makeFailoverStore([]);

        $this->assertInstanceOf(CanFlushLocks::class, $store);
    }

    public function testFlushLocksCallsFlushLocksOnAllBackingStores()
    {
        $storeA = new ArrayStore;
        $storeB = new ArrayStore;

        $storeA->lock('lock-a', 60)->get();
        $storeB->lock('lock-b', 60)->get();

        $cache = Mockery::mock(CacheManager::class);
        $cache->expects('store')->with('store-a')->andReturn(new Repository($storeA));
        $cache->expects('store')->with('store-b')->andReturn(new Repository($storeB));

        $failover = new FailoverStore($cache, new Dispatcher, ['store-a', 'store-b']);

        $result = $failover->flushLocks();

        $this->assertTrue($result);
        $this->assertEmpty($storeA->locks);
        $this->assertEmpty($storeB->locks);
    }

    public function testFlushLocksReturnsTrueWhenNoStoreSupportsIt()
    {
        $store = $this->makeFailoverStore([]);

        $this->assertTrue($store->flushLocks());
    }

    public function testLocksFailOverToTheNextStore()
    {
        $failingLock = Mockery::mock(Lock::class);
        $failingLock->allows('acquire')->andThrow(new RuntimeException('Connection refused'));
        $failingStore = Mockery::mock(Repository::class);
        $failingStore->allows('lock')->andReturn($failingLock);

        $arrayStore = new ArrayStore;

        $cache = Mockery::mock(CacheManager::class);
        $cache->allows('store')->with('failing')->andReturn($failingStore);
        $cache->allows('store')->with('array')->andReturn(new Repository($arrayStore));

        $failover = new FailoverStore($cache, new Dispatcher, ['failing', 'array']);

        $lock = $failover->lock('foo', 60);

        $this->assertTrue($lock->get());
        $this->assertFalse($failover->lock('foo', 60)->get());
        $this->assertArrayHasKey('foo', $arrayStore->locks);

        $this->assertTrue($lock->release());
        $this->assertArrayNotHasKey('foo', $arrayStore->locks);
    }

    public function testRestoredLocksFailOverToTheNextStore()
    {
        $failingLock = Mockery::mock(Lock::class);
        $failingLock->allows('release')->andThrow(new RuntimeException('Connection refused'));
        $failingStore = Mockery::mock(Repository::class);
        $failingStore->allows('lock')->andReturn($failingLock);

        $arrayStore = new ArrayStore;
        $arrayStore->lock('foo', 60, 'owner')->get();

        $cache = Mockery::mock(CacheManager::class);
        $cache->allows('store')->with('failing')->andReturn($failingStore);
        $cache->allows('store')->with('array')->andReturn(new Repository($arrayStore));

        $failover = new FailoverStore($cache, new Dispatcher, ['failing', 'array']);

        $this->assertTrue($failover->restoreLock('foo', 'owner')->release());
        $this->assertArrayNotHasKey('foo', $arrayStore->locks);
    }

    protected function makeFailoverStore(array $stores): FailoverStore
    {
        return new FailoverStore(
            Mockery::mock(CacheManager::class),
            new Dispatcher,
            $stores
        );
    }
}
