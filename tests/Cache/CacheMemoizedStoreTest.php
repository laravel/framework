<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\MemoizedStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Carbon;
use Memcached;
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
}
