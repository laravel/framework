<?php

namespace Illuminate\Tests\Cache;

use BadMethodCallException;
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

        $stub = new class implements \Illuminate\Contracts\Cache\LockProvider, \Illuminate\Contracts\Cache\Store
        {
            public function get($key)
            {
            return null;
            }
            public function many(array $keys)
            {
            return [];
            }
            public function put($key, $value, $seconds)
            {
            return true;
            }
            public function putMany(array $values, $seconds)
            {
            return true;
            }
            public function increment($key, $value = 1)
            {
            return false;
            }
            public function decrement($key, $value = 1)
            {
            return false;
            }
            public function forever($key, $value)
            {
            return true;
            }
            public function forget($key)
            {
            return true;
            }
            public function flush()
            {
            return true;
            }
            public function touch($key, $seconds)
            {
            return true;
            }
            public function getPrefix()
            {
            return '';
            }
            public function lock($name, $seconds = 0, $owner = null)
            {
            return new \Illuminate\Cache\NoLock($name, $seconds, $owner);
            }
            public function restoreLock($name, $owner)
            {
            return $this->lock($name, 0, $owner);
            }
        };

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
