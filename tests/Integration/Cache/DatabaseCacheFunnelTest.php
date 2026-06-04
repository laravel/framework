<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration('cache')]
class DatabaseCacheFunnelTest extends CacheFunnelTestCase
{
    use LazilyRefreshDatabase;

    protected function cache(): Repository
    {
        return Cache::store('database');
    }
}
