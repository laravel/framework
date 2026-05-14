<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\StorageStore;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Cache\Fixtures\ArrayFilesystem;
use PHPUnit\Framework\TestCase;

class CacheStorageStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testValuesCanBeStoredAndRetrieved()
    {
        $disk = new ArrayFilesystem;
        $store = new StorageStore($disk, 'cache', 'prefix:');

        $this->assertTrue($store->put('foo', 'bar', 60));
        $this->assertSame('bar', $store->get('foo'));
        $this->assertStringStartsWith('cache/', $store->path('foo'));
    }

    public function testExpiredItemsReturnNullAndGetDeleted()
    {
        Carbon::setTestNow(Carbon::now());

        $disk = new ArrayFilesystem;
        $store = new StorageStore($disk, 'cache');

        $store->put('foo', 'bar', 1);

        Carbon::setTestNow(Carbon::now()->addSeconds(2));

        $this->assertNull($store->get('foo'));
        $this->assertFalse($disk->exists($store->path('foo')));
    }

    public function testAddDoesNotOverwriteExistingValues()
    {
        $store = new StorageStore(new ArrayFilesystem, 'cache');

        $this->assertTrue($store->add('foo', 'bar', 60));
        $this->assertFalse($store->add('foo', 'baz', 60));
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testIncrementAndDecrementRetainExpiration()
    {
        Carbon::setTestNow(Carbon::now());

        $store = new StorageStore(new ArrayFilesystem, 'cache');
        $store->put('foo', 5, 60);

        $this->assertSame(7, $store->increment('foo', 2));
        $this->assertSame(4, $store->decrement('foo', 3));

        Carbon::setTestNow(Carbon::now()->addSeconds(61));

        $this->assertNull($store->get('foo'));
    }

    public function testTouchUpdatesExpiration()
    {
        Carbon::setTestNow(Carbon::now());

        $store = new StorageStore(new ArrayFilesystem, 'cache');
        $store->put('foo', 'bar', 2);

        Carbon::setTestNow(Carbon::now()->addSecond());

        $this->assertTrue($store->touch('foo', 60));

        Carbon::setTestNow(Carbon::now()->addSecond());

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testForgetRemovesFlexibleCreatedKeyOnlyWhenParentIsForgotten()
    {
        $disk = new ArrayFilesystem;
        $store = new StorageStore($disk, 'cache');

        $store->put('illuminate:cache:flexible:created:foo', true, 60);

        $this->assertFalse($store->forget('foo'));
        $this->assertTrue($disk->exists($store->path('illuminate:cache:flexible:created:foo')));

        $store->put('foo', 'bar', 60);

        $this->assertTrue($store->forget('foo'));
        $this->assertFalse($disk->exists($store->path('foo')));
        $this->assertFalse($disk->exists($store->path('illuminate:cache:flexible:created:foo')));
    }

    public function testFlushRemovesScopedDirectory()
    {
        $disk = new ArrayFilesystem;
        $store = new StorageStore($disk, 'cache');

        $store->put('foo', 'bar', 60);
        $disk->put('other/file', 'value');

        $this->assertTrue($store->flush());
        $this->assertNull($store->get('foo'));
        $this->assertTrue($disk->exists('other/file'));
    }
}
