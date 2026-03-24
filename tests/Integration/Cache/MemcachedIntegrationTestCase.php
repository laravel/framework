<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Support\Env;
use Memcached;
use Orchestra\Testbench\TestCase;

abstract class MemcachedIntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Determine whether there is a running Memcached instance
        $testConnection = new Memcached;

        $testConnection->addServer(
            Env::get('MEMCACHED_HOST', '127.0.0.1'),
            Env::get('MEMCACHED_PORT', 11211)
        );

        $testConnection->getVersion();

        if ($testConnection->getResultCode() > Memcached::RES_SUCCESS) {
            $this->markTestSkipped('Memcached could not establish a connection.');
        }

        $testConnection->quit();
    }
}
