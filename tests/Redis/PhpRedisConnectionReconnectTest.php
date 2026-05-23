<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Redis\Events\CommandFailed;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisCluster;
use RedisException;

class PhpRedisConnectionReconnectTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testCommandReturnsResultOnSuccess()
    {
        $client = m::mock(Redis::class);
        $client->shouldReceive('get')->once()->with('key')->andReturn('value');

        $connection = new PhpRedisConnection($client);

        $this->assertSame('value', $connection->command('get', ['key']));
    }

    public function testCommandRethrowsNonRedisExceptionWithoutReconnecting()
    {
        $client = m::mock(Redis::class);
        $client->shouldReceive('get')->once()->with('key')->andThrow(new \RuntimeException('boom'));

        $connector = function () {
            $this->fail('Connector should not be invoked for non-RedisException.');
        };

        $connection = new PhpRedisConnection($client, $connector);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $connection->command('get', ['key']);
    }

    public function testCommandRethrowsNonDisconnectionRedisExceptionWithoutReconnecting()
    {
        $client = m::mock(Redis::class);
        $client->shouldReceive('get')->once()->with('key')->andThrow(
            new RedisException('WRONGTYPE Operation against a key holding the wrong kind of value')
        );

        $connector = function () {
            $this->fail('Connector should not be invoked for non-disconnection RedisException.');
        };

        $connection = new PhpRedisConnection($client, $connector);

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('WRONGTYPE Operation against a key holding the wrong kind of value');

        $connection->command('get', ['key']);
    }

    #[DataProvider('disconnectionErrorMessages')]
    public function testCommandReconnectsAndRetriesOnDisconnectionError(string $errorMessage)
    {
        $oldClient = m::mock(Redis::class);
        $oldClient->shouldReceive('get')->once()->with('key')->andThrow(new RedisException($errorMessage));

        $newClient = m::mock(Redis::class);
        $newClient->shouldReceive('get')->once()->with('key')->andReturn('value');

        $connectorCalled = 0;
        $connector = function () use ($newClient, &$connectorCalled) {
            $connectorCalled++;

            return $newClient;
        };

        $connection = new PhpRedisConnection($oldClient, $connector);

        $this->assertSame('value', $connection->command('get', ['key']));
        $this->assertSame(1, $connectorCalled, 'Connector should be invoked exactly once.');
    }

    public function testCommandThrowsWhenRetryAlsoFails()
    {
        $oldClient = m::mock(Redis::class);
        $oldClient->shouldReceive('get')->once()->with('key')->andThrow(
            new RedisException('Redis server went away')
        );

        $newClient = m::mock(Redis::class);
        $newClient->shouldReceive('get')->once()->with('key')->andThrow(
            new RedisException('Redis server went away again')
        );

        $connector = fn () => $newClient;

        $connection = new PhpRedisConnection($oldClient, $connector);

        $this->expectException(RedisException::class);
        // The exception from the retry attempt should bubble up (not the original one).
        $this->expectExceptionMessage('Redis server went away again');

        $connection->command('get', ['key']);
    }

    public function testCommandThrowsWithoutAttemptingRetryWhenNoConnectorIsAvailable()
    {
        $client = m::mock(Redis::class);
        // Only one call expected — without a connector we cannot rebuild the client
        // so retrying on the same broken client would be wasted work.
        $client->shouldReceive('get')->once()->with('key')->andThrow(
            new RedisException('Redis server went away')
        );

        $connection = new PhpRedisConnection($client); // no connector

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('Redis server went away');

        $connection->command('get', ['key']);
    }

    public function testReconnectAndRetryAppliesToCallsRoutedThroughMagicMethod()
    {
        // Most user code calls $conn->get('key') directly, which goes through
        // __call() and eventually lands in command(). This test pins down that
        // the magic-method path benefits from the same reconnect-and-retry.
        $oldClient = m::mock(Redis::class);
        $oldClient->shouldReceive('get')->once()->with('key')->andThrow(
            new RedisException('Redis server went away')
        );

        $newClient = m::mock(Redis::class);
        $newClient->shouldReceive('get')->once()->with('key')->andReturn('value');

        $connector = fn () => $newClient;

        $connection = new PhpRedisConnection($oldClient, $connector);

        $this->assertSame('value', $connection->get('key'));
    }

    public function testClusterConnectionInheritsReconnectAndRetryWhenAConnectorIsProvided()
    {
        // PhpRedisClusterConnection extends PhpRedisConnection and inherits
        // command() unchanged, so the new reconnect-and-retry behavior is
        // available at the class level when a connector is supplied.
        //
        // Note: PhpRedisConnector::connectToCluster() does not currently pass
        // a connector when constructing PhpRedisClusterConnection, so in
        // production the new branch never engages for cluster connections.
        // Wiring that up is left to a follow-up PR; this test pins down the
        // class-level inheritance so that follow-up will work without further
        // changes to the connection class.
        $oldClient = m::mock(RedisCluster::class);
        $oldClient->shouldReceive('get')->once()->with('key')->andThrow(
            new RedisException('Redis server went away')
        );

        $newClient = m::mock(RedisCluster::class);
        $newClient->shouldReceive('get')->once()->with('key')->andReturn('value');

        $connector = fn () => $newClient;

        $connection = new PhpRedisClusterConnection($oldClient, $connector);

        $this->assertSame('value', $connection->command('get', ['key']));
    }

    public function testReconnectAndRetryDispatchesCommandFailedThenCommandExecuted()
    {
        // A successfully-recovered command still dispatches the original failure
        // event (so error-rate metrics see the disconnection happened), followed
        // by a CommandExecuted event from the retry.
        $oldClient = m::mock(Redis::class);
        $oldClient->shouldReceive('get')->once()->with('key')->andThrow(
            new RedisException('Redis server went away')
        );

        $newClient = m::mock(Redis::class);
        $newClient->shouldReceive('get')->once()->with('key')->andReturn('value');

        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->ordered()->with(m::type(CommandFailed::class));
        $events->shouldReceive('dispatch')->once()->ordered()->with(m::type(CommandExecuted::class));

        $connection = new PhpRedisConnection($oldClient, fn () => $newClient);
        $connection->setEventDispatcher($events);

        $this->assertSame('value', $connection->command('get', ['key']));
    }

    public function testFailedRetryDispatchesTwoCommandFailedEvents()
    {
        // If both the original and the retry fail, both should surface as
        // CommandFailed so observers don't lose either signal.
        $oldClient = m::mock(Redis::class);
        $oldClient->shouldReceive('get')->once()->andThrow(new RedisException('Redis server went away'));

        $newClient = m::mock(Redis::class);
        $newClient->shouldReceive('get')->once()->andThrow(new RedisException('Redis server went away again'));

        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->twice()->with(m::type(CommandFailed::class));
        $events->shouldNotReceive('dispatch')->with(m::type(CommandExecuted::class));

        $connection = new PhpRedisConnection($oldClient, fn () => $newClient);
        $connection->setEventDispatcher($events);

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('Redis server went away again');

        $connection->command('get', ['key']);
    }

    #[DataProvider('transactionControlCommands')]
    public function testCommandDoesNotRetryTransactionControlCommands(string $controlMethod, array $parameters)
    {
        $oldClient = m::mock(Redis::class);
        $oldClient->shouldReceive($controlMethod)->once()->andThrow(
            new RedisException('Redis server went away')
        );

        // The connector should still run (to rebuild the client for the next
        // call) but the new client must NOT receive the retried control command,
        // since the new connection has no MULTI/WATCH state to act on.
        $newClient = m::mock(Redis::class);
        $newClient->shouldNotReceive($controlMethod);

        $connectorCalled = 0;
        $connector = function () use ($newClient, &$connectorCalled) {
            $connectorCalled++;

            return $newClient;
        };

        $connection = new PhpRedisConnection($oldClient, $connector);

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('Redis server went away');

        try {
            $connection->command($controlMethod, $parameters);
        } finally {
            $this->assertSame(1, $connectorCalled, 'Connector should run exactly once to rebuild the client for the next call.');
        }
    }

    public function testSubsequentNonTransactionCommandUsesTheRebuiltClient()
    {
        // After a transaction-control command fails on a lost connection, the
        // next non-transaction command should land on the rebuilt client and
        // succeed (matching the pre-existing reconnect invariant from #41546).
        $oldClient = m::mock(Redis::class);
        $oldClient->shouldReceive('multi')->once()->andThrow(new RedisException('Redis server went away'));

        $newClient = m::mock(Redis::class);
        $newClient->shouldReceive('get')->once()->with('key')->andReturn('value');

        $connector = fn () => $newClient;

        $connection = new PhpRedisConnection($oldClient, $connector);

        try {
            $connection->command('multi', []);
        } catch (RedisException) {
            // Expected — the multi() call surfaces the disconnection.
        }

        $this->assertSame('value', $connection->command('get', ['key']));
    }

    public static function disconnectionErrorMessages(): array
    {
        return [
            'went away' => ['Redis server went away'],
            'socket' => ['socket error on read socket'],
            'Error while reading' => ['Error while reading line from the server'],
            'read error on connection' => ['read error on connection to 127.0.0.1:6379'],
            'READONLY' => ['READONLY You can\'t write against a read only replica.'],
            'Connection lost' => ['Connection lost'],
        ];
    }

    public static function transactionControlCommands(): array
    {
        return [
            'multi' => ['multi', []],
            'exec' => ['exec', []],
            'discard' => ['discard', []],
            'watch' => ['watch', ['key']],
            'unwatch' => ['unwatch', []],
            'MULTI (uppercase)' => ['MULTI', []],
            'Exec (mixed case)' => ['Exec', []],
        ];
    }
}
