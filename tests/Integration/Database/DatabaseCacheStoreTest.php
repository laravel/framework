<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration('cache')]
class DatabaseCacheStoreTest extends DatabaseTestCase
{
    public function testValueCanStoreNewCache()
    {
        $store = $this->getStore();

        $store->put('foo', 'bar', 60);

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testPutOperationShouldNotStoreExpired()
    {
        $store = $this->getStore();

        $store->put('foo', 'bar', 0);

        $this->assertDatabaseMissing($this->getCacheTableName(), ['key' => $this->withCachePrefix('foo')]);
    }

    public function testValueCanUpdateExistCache()
    {
        $store = $this->getStore();

        $store->put('foo', 'bar', 60);
        $store->put('foo', 'new-bar', 60);

        $this->assertSame('new-bar', $store->get('foo'));
    }

    public function testValueCanUpdateExistCacheInTransaction()
    {
        $store = $this->getStore();

        $store->put('foo', 'bar', 60);

        DB::beginTransaction();
        $store->put('foo', 'new-bar', 60);
        DB::commit();

        $this->assertSame('new-bar', $store->get('foo'));
    }

    public function testAddOperationShouldNotStoreExpired()
    {
        $store = $this->getStore();

        $result = $store->add('foo', 'bar', 0);

        $this->assertFalse($result);
        $this->assertDatabaseMissing($this->getCacheTableName(), ['key' => $this->withCachePrefix('foo')]);
    }

    public function testAddOperationCanStoreNewCache()
    {
        $store = $this->getStore();

        $result = $store->add('foo', 'bar', 60);

        $this->assertTrue($result);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testAddOperationShouldNotUpdateExistCache()
    {
        $store = $this->getStore();

        $store->add('foo', 'bar', 60);
        $result = $store->add('foo', 'new-bar', 60);

        $this->assertFalse($result);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testAddOperationShouldNotUpdateExistCacheInTransaction()
    {
        $store = $this->getStore();

        $store->add('foo', 'bar', 60);

        DB::beginTransaction();
        $result = $store->add('foo', 'new-bar', 60);
        DB::commit();

        $this->assertFalse($result);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testAddOperationCanUpdateIfCacheExpired()
    {
        $store = $this->getStore();

        $this->insertToCacheTable('foo', 'bar', 0);
        $result = $store->add('foo', 'new-bar', 60);

        $this->assertTrue($result);
        $this->assertSame('new-bar', $store->get('foo'));
    }

    public function testAddOperationCanUpdateIfCacheExpiredInTransaction()
    {
        $store = $this->getStore();

        $this->insertToCacheTable('foo', 'bar', 0);

        DB::beginTransaction();
        $result = $store->add('foo', 'new-bar', 60);
        DB::commit();

        $this->assertTrue($result);
        $this->assertSame('new-bar', $store->get('foo'));
    }

    public function testGetOperationReturnNullIfExpired()
    {
        $store = $this->getStore();

        $this->insertToCacheTable('foo', 'bar', 0);

        $result = $store->get('foo');

        $this->assertNull($result);
    }

    public function testGetOperationCanDeleteExpired()
    {
        $store = $this->getStore();

        $this->insertToCacheTable('foo', 'bar', 0);

        $store->get('foo');

        $this->assertDatabaseMissing($this->getCacheTableName(), ['key' => $this->withCachePrefix('foo')]);
    }

    public function testForgetIfExpiredOperationCanDeleteExpired()
    {
        $store = $this->getStore();

        $this->insertToCacheTable('foo', 'bar', 0);

        $store->forgetIfExpired('foo');

        $this->assertDatabaseMissing($this->getCacheTableName(), ['key' => $this->withCachePrefix('foo')]);
    }

    public function testForgetIfExpiredOperationShouldNotDeleteUnExpired()
    {
        $store = $this->getStore();

        $store->put('foo', 'bar', 60);

        $store->forgetIfExpired('foo');

        $this->assertDatabaseHas($this->getCacheTableName(), ['key' => $this->withCachePrefix('foo')]);
    }

    /**
     * @return \Illuminate\Cache\DatabaseStore
     */
    protected function getStore()
    {
        return Cache::store('database');
    }

    protected function getCacheTableName()
    {
        return config('cache.stores.database.table');
    }

    protected function withCachePrefix(string $key)
    {
        return config('cache.prefix').$key;
    }

    protected function insertToCacheTable(string $key, $value, $ttl = 60)
    {
        DB::table($this->getCacheTableName())
            ->insert(
                [
                    'key' => $this->withCachePrefix($key),
                    'value' => $value,
                    'expiration' => Carbon::now()->addSeconds($ttl)->getTimestamp(),
                ]
            );
    }
}
