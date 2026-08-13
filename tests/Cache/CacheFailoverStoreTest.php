<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\FailoverStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\CanFlushLocks;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use PHPUnit\Framework\TestCase;

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

        $failover = new FailoverStore($cache, Mockery::mock(Dispatcher::class), ['store-a', 'store-b']);

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

    protected function makeFailoverStore(array $stores): FailoverStore
    {
        return new FailoverStore(
            Mockery::mock(CacheManager::class),
            Mockery::mock(Dispatcher::class),
            $stores
        );
    }
}
