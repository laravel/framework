<?php

namespace Illuminate\Tests\Integration\Cache\Database;

use Illuminate\Foundation\Testing\Concerns\InteractsWithCacheTable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Tests\Integration\Database\MySql\MySqlTestCase;

class MysqlCacheStoreTest extends MySqlTestCase
{
    use InteractsWithCacheTable;

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        $this->createCacheTables();
    }

    protected function destroyDatabaseMigrations()
    {
        $this->dropCacheTables();
    }

    public function testValueCanStoreNewCache()
    {
        $store = $this->getStore();

        $store->put('foo', 'bar', 60);

        $this->assertSame('bar', $store->get('foo'));
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

    protected function getStore()
    {
        return Cache::store('database');
    }
}
