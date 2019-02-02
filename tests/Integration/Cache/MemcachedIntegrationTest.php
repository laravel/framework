<?php

namespace Illuminate\Tests\Integration\Cache;

use Memcached;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
abstract class MemcachedIntegrationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        if (! extension_loaded('memcached')) {
            $this->markTestSkipped('Memcached module not installed');
        }

        // Determine whether there is a running Memcached instance
        $testConnection = new Memcached;

        $testConnection->addServer(
            env('MEMCACHED_HOST', '127.0.0.1'),
            env('MEMCACHED_PORT', 11211)
        );

        $testConnection->getVersion();

        if ($testConnection->getResultCode() > Memcached::RES_SUCCESS) {
            $this->markTestSkipped('Memcached could not establish a connection');
        }

        $testConnection->quit();
    }
}
