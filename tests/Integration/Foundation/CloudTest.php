<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\Cloud;
use Orchestra\Testbench\TestCase;

class CloudTest extends TestCase
{
    public function test_it_can_resolve_core_container_aliases()
    {
        $this->app['config']->set('database.connections.pgsql', [
            'host' => 'test-pooler.pg.laravel.cloud',
            'username' => 'test-username',
            'password' => 'test-password',
        ]);

        Cloud::configureUnpooledPostgresConnection($this->app);

        $this->assertEquals([
            'host' => 'test.pg.laravel.cloud',
            'username' => 'test-username',
            'password' => 'test-password',
        ], $this->app['config']->get('database.connections.pgsql-unpooled'));
    }
}
