<?php

namespace Illuminate\Tests\Cache;

use BadMethodCallException;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\MemoizedStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheMemoizedStoreTest extends TestCase
{
    public function testTouchExtendsTtl(): void
    {
        $store = new MemoizedStore('test', new Repository(new ArrayStore));

        Carbon::setTestNow($now = Carbon::now());

        $store->put('foo', 'bar', 30);
        $store->touch('foo', 60);

        Carbon::setTestNow($now->addSeconds(45));

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testLocksCanBeFlushedWhenUnderlyingStoreSupportsIt(): void
    {
        $store = new MemoizedStore('test', new Repository(new ArrayStore));
        $this->assertTrue($store->flushLocks());
    }

    public function testFlushLocksThrowsWhenUnderlyingStoreDoesNotSupportIt(): void
    {
        $this->expectException(BadMethodCallException::class);

        $stub = m::mock(Store::class);
        (new MemoizedStore('test', new Repository($stub)))->flushLocks();
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testHasSeparateLockStoreDelegatestoUnderlyingStore(): void
    {
        $withSeparate = new MemoizedStore('test', new Repository(new ArrayStore));
        $this->assertTrue($withSeparate->hasSeparateLockStore());

        $withoutSeparate = new MemoizedStore('test', new Repository(new NullStore));
        $this->assertFalse($withoutSeparate->hasSeparateLockStore());
    }
}
