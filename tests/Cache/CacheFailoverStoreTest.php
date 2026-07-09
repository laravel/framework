<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\FailoverStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\CanFlushLocks;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheFailoverStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

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

        $cache = m::mock(CacheManager::class);
        $cache->shouldReceive('store')->with('store-a')->andReturn(new Repository($storeA));
        $cache->shouldReceive('store')->with('store-b')->andReturn(new Repository($storeB));

        $failover = new FailoverStore($cache, m::mock(Dispatcher::class), ['store-a', 'store-b']);

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
            m::mock(CacheManager::class),
            m::mock(Dispatcher::class),
            $stores
        );
    }
}
