<?php

namespace Illuminate\Tests\Cache;

use BadMethodCallException;
use Illuminate\Cache\ApcStore;
use Illuminate\Cache\ApcWrapper;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\MemoizedStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Carbon;
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

        $stub = new ApcStore(new ApcWrapper);
        (new MemoizedStore('test', new Repository($stub)))->flushLocks();
    }

    public function testHasSeparateLockStoreDelegatestoUnderlyingStore(): void
    {
        $withSeparate = new MemoizedStore('test', new Repository(new ArrayStore));
        $this->assertTrue($withSeparate->hasSeparateLockStore());

        $withoutSeparate = new MemoizedStore('test', new Repository(new NullStore));
        $this->assertFalse($withoutSeparate->hasSeparateLockStore());
    }
}
