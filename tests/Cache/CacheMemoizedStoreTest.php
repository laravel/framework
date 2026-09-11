<?php

namespace Illuminate\Tests\Cache;

use BadMethodCallException;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\MemoizedStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Carbon;
use Mockery;
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

    public function testAddDoesNotOverwriteValueWrittenAfterMissWasMemoized(): void
    {
        $repository = new Repository(new ArrayStore);
        $memoized = new Repository(new MemoizedStore('test', $repository));

        $this->assertNull($memoized->get('foo'));

        $repository->put('foo', 'bar', 30);

        $this->assertFalse($memoized->add('foo', 'baz', 30));
        $this->assertSame('bar', $memoized->get('foo'));
        $this->assertSame('bar', $repository->get('foo'));

        $this->assertTrue($memoized->add('new', 'value', 30));
        $this->assertSame('value', $memoized->get('new'));
        $this->assertSame('value', $repository->get('new'));
    }

    public function testLocksCanBeFlushedWhenUnderlyingStoreSupportsIt(): void
    {
        $store = new MemoizedStore('test', new Repository(new ArrayStore));
        $this->assertTrue($store->flushLocks());
    }

    public function testFlushLocksThrowsWhenUnderlyingStoreDoesNotSupportIt(): void
    {
        $this->expectException(BadMethodCallException::class);

        $stub = Mockery::mock(Store::class);
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
