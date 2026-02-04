<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\FailoverConnection;
use Illuminate\Redis\RedisManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FailoverConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testReadCommandTriesAllConnectionsInOrder()
    {
        $primary = m::mock(Connection::class);
        $fallback = m::mock(Connection::class);
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('fallback')->andReturn(['read_only' => true]);

        $manager->shouldReceive('connection')
            ->with('primary')->andReturn($primary);
        $manager->shouldReceive('connection')
            ->with('fallback')->andReturn($fallback);

        $primary->shouldReceive('command')
            ->once()->with('get', ['foo'])->andThrow(new RuntimeException('Connection refused'));
        $fallback->shouldReceive('command')
            ->once()->with('get', ['foo'])->andReturn('fallback-value');

        $connection = new FailoverConnection($manager, ['primary', 'fallback']);

        $this->assertSame('fallback-value', $connection->command('get', ['foo']));
    }

    public function testReadCommandUsesPrimaryWhenPrimarySucceeds()
    {
        $primary = m::mock(Connection::class);
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('fallback')->andReturn(['read_only' => true]);

        $manager->shouldReceive('connection')
            ->with('primary')->andReturn($primary);

        $primary->shouldReceive('command')
            ->once()->with('get', ['foo'])->andReturn('primary-value');

        $connection = new FailoverConnection($manager, ['primary', 'fallback']);

        $this->assertSame('primary-value', $connection->command('get', ['foo']));
    }

    public function testWriteCommandSkipsReadOnlyConnections()
    {
        $primary = m::mock(Connection::class);
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('fallback')->andReturn(['read_only' => true]);

        $manager->shouldReceive('connection')
            ->with('primary')->andReturn($primary);
        // fallback should never be resolved for write commands

        $primary->shouldReceive('command')
            ->once()->with('set', ['foo', 'bar'])->andThrow(new RuntimeException('Connection refused'));

        $connection = new FailoverConnection($manager, ['primary', 'fallback']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        $connection->command('set', ['foo', 'bar']);
    }

    public function testWriteCommandTriesAllWritableConnections()
    {
        $primary = m::mock(Connection::class);
        $secondary = m::mock(Connection::class);
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('secondary')->andReturn(['read_only' => false]);

        $manager->shouldReceive('connection')
            ->with('primary')->andReturn($primary);
        $manager->shouldReceive('connection')
            ->with('secondary')->andReturn($secondary);

        $primary->shouldReceive('command')
            ->once()->with('set', ['foo', 'bar'])->andThrow(new RuntimeException('Connection refused'));
        $secondary->shouldReceive('command')
            ->once()->with('set', ['foo', 'bar'])->andReturn(true);

        $connection = new FailoverConnection($manager, ['primary', 'secondary']);

        $this->assertTrue($connection->command('set', ['foo', 'bar']));
    }

    public function testThrowsExceptionWhenNoWritableConnections()
    {
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('readonly1')->andReturn(['read_only' => true]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('readonly2')->andReturn(['read_only' => true]);

        $connection = new FailoverConnection($manager, ['readonly1', 'readonly2']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No writable Redis connections available in failover.');

        $connection->command('set', ['foo', 'bar']);
    }

    public function testThrowsExceptionWhenNoConnectionsConfigured()
    {
        $manager = m::mock(RedisManager::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('At least one connection must be specified for failover.');

        new FailoverConnection($manager, []);
    }

    public function testInlineReadOnlyOverridesConnectionConfig()
    {
        $primary = m::mock(Connection::class);
        $manager = m::mock(RedisManager::class);

        // secondary has read_only => false in config, but we override to true inline
        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('secondary')->andReturn(['read_only' => false]);

        $manager->shouldReceive('connection')
            ->with('primary')->andReturn($primary);

        $primary->shouldReceive('command')
            ->once()->with('set', ['foo', 'bar'])->andThrow(new RuntimeException('Connection refused'));

        // Override secondary to be read_only via inline config
        $connection = new FailoverConnection($manager, [
            'primary',
            ['name' => 'secondary', 'read_only' => true],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        $connection->command('set', ['foo', 'bar']);
    }

    public function testGetWritableConnectionsReturnsOnlyWritable()
    {
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('secondary')->andReturn(['read_only' => false]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('fallback')->andReturn(['read_only' => true]);

        $connection = new FailoverConnection($manager, ['primary', 'secondary', 'fallback']);

        $this->assertEquals(['primary', 'secondary'], $connection->getWritableConnections());
    }

    public function testGetConnectionsReturnsAllConnections()
    {
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('fallback')->andReturn(['read_only' => true]);

        $connection = new FailoverConnection($manager, ['primary', 'fallback']);

        $this->assertEquals(['primary', 'fallback'], $connection->getConnections());
    }

    public function testClientReturnsFirstConnectionClient()
    {
        $primary = m::mock(Connection::class);
        $client = new \stdClass;
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);

        $manager->shouldReceive('connection')
            ->with('primary')->andReturn($primary);

        $primary->shouldReceive('client')->andReturn($client);

        $connection = new FailoverConnection($manager, ['primary']);

        $this->assertSame($client, $connection->client());
    }

    public function testConnectionsAreResolvedLazily()
    {
        $primary = m::mock(Connection::class);
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);
        $manager->shouldReceive('getConnectionConfig')
            ->with('fallback')->andReturn(['read_only' => true]);

        // Only primary should be resolved since it succeeds
        $manager->shouldReceive('connection')
            ->once()->with('primary')->andReturn($primary);
        $manager->shouldNotReceive('connection')->with('fallback');

        $primary->shouldReceive('command')
            ->once()->with('get', ['foo'])->andReturn('value');

        $connection = new FailoverConnection($manager, ['primary', 'fallback']);

        $this->assertSame('value', $connection->command('get', ['foo']));
    }

    public function testConnectionsAreCached()
    {
        $primary = m::mock(Connection::class);
        $manager = m::mock(RedisManager::class);

        $manager->shouldReceive('getConnectionConfig')
            ->with('primary')->andReturn([]);

        // Should only resolve once even with multiple commands
        $manager->shouldReceive('connection')
            ->once()->with('primary')->andReturn($primary);

        $primary->shouldReceive('command')
            ->twice()->andReturn('value');

        $connection = new FailoverConnection($manager, ['primary']);

        $connection->command('get', ['foo']);
        $connection->command('get', ['bar']);
    }

    public function testVariousReadOnlyCommandsUseFailover()
    {
        $readOnlyCommands = ['get', 'mget', 'exists', 'ttl', 'llen', 'hget', 'zcard', 'smembers', 'scan'];

        foreach ($readOnlyCommands as $cmd) {
            $primary = m::mock(Connection::class);
            $fallback = m::mock(Connection::class);
            $manager = m::mock(RedisManager::class);

            $manager->shouldReceive('getConnectionConfig')
                ->with('primary')->andReturn([]);
            $manager->shouldReceive('getConnectionConfig')
                ->with('fallback')->andReturn(['read_only' => true]);

            $manager->shouldReceive('connection')
                ->with('primary')->andReturn($primary);
            $manager->shouldReceive('connection')
                ->with('fallback')->andReturn($fallback);

            $primary->shouldReceive('command')
                ->once()->andThrow(new RuntimeException('fail'));
            $fallback->shouldReceive('command')
                ->once()->andReturn('fallback-result');

            $connection = new FailoverConnection($manager, ['primary', 'fallback']);

            $this->assertSame('fallback-result', $connection->command($cmd, []), "Command {$cmd} should use failover");

            m::close();
        }
    }

    public function testVariousWriteCommandsSkipReadOnlyConnections()
    {
        $writeCommands = ['set', 'setex', 'del', 'lpush', 'rpush', 'zadd', 'hset', 'incr', 'decr', 'expire'];

        foreach ($writeCommands as $cmd) {
            $primary = m::mock(Connection::class);
            $manager = m::mock(RedisManager::class);

            $manager->shouldReceive('getConnectionConfig')
                ->with('primary')->andReturn([]);
            $manager->shouldReceive('getConnectionConfig')
                ->with('fallback')->andReturn(['read_only' => true]);

            $manager->shouldReceive('connection')
                ->with('primary')->andReturn($primary);

            $primary->shouldReceive('command')
                ->once()->andThrow(new RuntimeException('fail'));

            $connection = new FailoverConnection($manager, ['primary', 'fallback']);

            try {
                $connection->command($cmd, []);
                $this->fail("Command {$cmd} should have thrown exception");
            } catch (RuntimeException $e) {
                $this->assertSame('fail', $e->getMessage());
            }

            m::close();
        }
    }
}
