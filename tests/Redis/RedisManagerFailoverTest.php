<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Foundation\Application;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\FailoverConnection;
use Illuminate\Redis\RedisManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RedisManagerFailoverTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testConnectionReturnsFailoverConnectionWhenConnectionsConfigured()
    {
        $app = new Application;
        $cacheConnection = m::mock(Connection::class);
        $cacheConnection->shouldReceive('setName')->with('failover')->andReturnSelf();

        $connector = m::mock(\Illuminate\Contracts\Redis\Connector::class);
        $connector->shouldReceive('connect')->andReturn($cacheConnection);

        $config = [
            'options' => [],
            'cache' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 1,
            ],
            'fallback' => [
                'host' => '127.0.0.1',
                'port' => 6380,
                'database' => 0,
                'read_only' => true,
            ],
            'failover' => [
                'connections' => ['cache', 'fallback'],
            ],
        ];

        $manager = new RedisManager($app, 'phpredis', $config);
        $manager->extend('phpredis', function () use ($connector) {
            return $connector;
        });

        $connection = $manager->connection('failover');

        $this->assertInstanceOf(FailoverConnection::class, $connection);
    }

    public function testFailoverConnectionReadsReadOnlyFromConnectionConfig()
    {
        $app = new Application;
        $cacheConnection = m::mock(Connection::class);
        $cacheConnection->shouldReceive('setName')->andReturnSelf();
        $cacheConnection->shouldReceive('command')->andThrow(new \RuntimeException('fail'));

        $fallbackConnection = m::mock(Connection::class);
        $fallbackConnection->shouldReceive('setName')->andReturnSelf();
        $fallbackConnection->shouldReceive('command')->andReturn('fallback-value');

        $connector = m::mock(\Illuminate\Contracts\Redis\Connector::class);
        $connector->shouldReceive('connect')->andReturnUsing(function ($config) use ($cacheConnection, $fallbackConnection) {
            return ($config['database'] ?? null) === 0 ? $fallbackConnection : $cacheConnection;
        });

        $config = [
            'options' => [],
            'cache' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 1,
            ],
            'fallback' => [
                'host' => '127.0.0.1',
                'port' => 6380,
                'database' => 0,
                'read_only' => true,
            ],
            'failover' => [
                'connections' => ['cache', 'fallback'],
            ],
        ];

        $manager = new RedisManager($app, 'phpredis', $config);
        $manager->extend('phpredis', function () use ($connector) {
            return $connector;
        });

        $connection = $manager->connection('failover');

        // Read command should try both connections
        $this->assertSame('fallback-value', $connection->command('get', ['foo']));
    }

    public function testFailoverConnectionSkipsReadOnlyForWriteCommands()
    {
        $app = new Application;
        $cacheConnection = m::mock(Connection::class);
        $cacheConnection->shouldReceive('setName')->andReturnSelf();
        $cacheConnection->shouldReceive('command')->once()->with('set', ['foo', 'bar'])->andThrow(new \RuntimeException('Connection refused'));

        $fallbackConnection = m::mock(Connection::class);
        // fallback should never receive the write command since it's read_only
        $fallbackConnection->shouldNotReceive('command');

        $connector = m::mock(\Illuminate\Contracts\Redis\Connector::class);
        $connector->shouldReceive('connect')->andReturnUsing(function ($config) use ($cacheConnection, $fallbackConnection) {
            return ($config['database'] ?? null) === 0 ? $fallbackConnection : $cacheConnection;
        });

        $config = [
            'options' => [],
            'cache' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 1,
            ],
            'fallback' => [
                'host' => '127.0.0.1',
                'port' => 6380,
                'database' => 0,
                'read_only' => true,
            ],
            'failover' => [
                'connections' => ['cache', 'fallback'],
            ],
        ];

        $manager = new RedisManager($app, 'phpredis', $config);
        $manager->extend('phpredis', function () use ($connector) {
            return $connector;
        });

        $connection = $manager->connection('failover');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        $connection->command('set', ['foo', 'bar']);
    }

    public function testParseConnectionConfigurationStripsReadOnlyAndConnections()
    {
        $app = new Application;
        $configPassedToConnector = null;

        $connector = m::mock(\Illuminate\Contracts\Redis\Connector::class);
        $connector->shouldReceive('connect')->once()->andReturnUsing(function ($config) use (&$configPassedToConnector) {
            $configPassedToConnector = $config;
            $connection = m::mock(Connection::class);
            $connection->shouldReceive('setName')->andReturnSelf();
            return $connection;
        });

        $config = [
            'options' => [],
            'cache' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 1,
                'read_only' => false,
            ],
        ];

        $manager = new RedisManager($app, 'phpredis', $config);
        $manager->extend('phpredis', function () use ($connector) {
            return $connector;
        });

        $manager->connection('cache');

        $this->assertArrayNotHasKey('read_only', $configPassedToConnector);
    }

    public function testGetConnectionConfigReturnsConnectionConfiguration()
    {
        $app = new Application;

        $config = [
            'options' => [],
            'cache' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 1,
                'read_only' => false,
            ],
            'fallback' => [
                'host' => '127.0.0.1',
                'port' => 6380,
                'database' => 0,
                'read_only' => true,
            ],
        ];

        $manager = new RedisManager($app, 'phpredis', $config);

        $cacheConfig = $manager->getConnectionConfig('cache');
        $this->assertSame('127.0.0.1', $cacheConfig['host']);
        $this->assertFalse($cacheConfig['read_only']);

        $fallbackConfig = $manager->getConnectionConfig('fallback');
        $this->assertTrue($fallbackConfig['read_only']);

        $nonExistentConfig = $manager->getConnectionConfig('nonexistent');
        $this->assertSame([], $nonExistentConfig);
    }

    public function testConnectionReturnsRegularConnectionWhenNoConnectionsConfigured()
    {
        $app = new Application;
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('setName')->with('cache')->andReturnSelf();

        $connector = m::mock(\Illuminate\Contracts\Redis\Connector::class);
        $connector->shouldReceive('connect')->once()->andReturn($connection);

        $config = [
            'options' => [],
            'cache' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 1,
            ],
        ];

        $manager = new RedisManager($app, 'phpredis', $config);
        $manager->extend('phpredis', function () use ($connector) {
            return $connector;
        });

        $result = $manager->connection('cache');

        $this->assertSame($connection, $result);
    }

    public function testFailoverWithMultipleWritableConnections()
    {
        $app = new Application;
        $primaryConnection = m::mock(Connection::class);
        $primaryConnection->shouldReceive('setName')->andReturnSelf();
        $primaryConnection->shouldReceive('command')->once()->with('set', ['foo', 'bar'])->andThrow(new \RuntimeException('fail'));

        $secondaryConnection = m::mock(Connection::class);
        $secondaryConnection->shouldReceive('setName')->andReturnSelf();
        $secondaryConnection->shouldReceive('command')->once()->with('set', ['foo', 'bar'])->andReturn(true);

        $connector = m::mock(\Illuminate\Contracts\Redis\Connector::class);
        $connector->shouldReceive('connect')->andReturnUsing(function ($config) use ($primaryConnection, $secondaryConnection) {
            return ($config['database'] ?? null) === 2 ? $secondaryConnection : $primaryConnection;
        });

        $config = [
            'options' => [],
            'primary' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 1,
                'read_only' => false,
            ],
            'secondary' => [
                'host' => '127.0.0.1',
                'port' => 6380,
                'database' => 2,
                'read_only' => false,
            ],
            'failover' => [
                'connections' => ['primary', 'secondary'],
            ],
        ];

        $manager = new RedisManager($app, 'phpredis', $config);
        $manager->extend('phpredis', function () use ($connector) {
            return $connector;
        });

        $connection = $manager->connection('failover');

        // Write command should try both writable connections
        $this->assertTrue($connection->command('set', ['foo', 'bar']));
    }
}
