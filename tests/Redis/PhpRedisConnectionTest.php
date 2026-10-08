<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Mockery;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use RedisCluster;
use RedisException;

#[RequiresPhpExtension('redis')]
class PhpRedisConnectionTest extends TestCase
{
    public function testEvalReturnsResultOnSuccess()
    {
        $client = new FakePhpRedisClient;
        $client->evalResult = 1;

        $connection = new PhpRedisConnection($client);

        $this->assertSame(1, $connection->eval('return 1', 0));
        $this->assertTrue($client->lastErrorCleared);
    }

    public function testEvalThrowsWhenScriptFailsOnTheServer()
    {
        $client = new FakePhpRedisClient;
        $client->evalResult = false;
        $client->errorOnEval = "ERR ACL failure in script: User laravel has no permissions to run the 'rpush' command";

        $connection = new PhpRedisConnection($client);

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage("ERR ACL failure in script: User laravel has no permissions to run the 'rpush' command");

        $connection->eval('return 1', 0);
    }

    public function testEvalDoesNotThrowWhenScriptLegitimatelyReturnsFalse()
    {
        $client = new FakePhpRedisClient;
        $client->evalResult = false;

        $connection = new PhpRedisConnection($client);

        $this->assertFalse($connection->eval('return false', 0));
    }

    public function testEvalShaThrowsWhenScriptFailsOnTheServer()
    {
        $client = new FakePhpRedisClient;
        $client->evalResult = false;
        $client->errorOnEval = "ERR ACL failure in script: User laravel has no permissions to run the 'rpush' command";

        $connection = new PhpRedisConnection($client);

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage("ERR ACL failure in script: User laravel has no permissions to run the 'rpush' command");

        $connection->evalsha('return 1', 0);
    }

    public function testEvalShaDoesNotThrowWhenScriptLegitimatelyReturnsFalse()
    {
        $client = new FakePhpRedisClient;
        $client->evalResult = false;

        $connection = new PhpRedisConnection($client);

        $this->assertFalse($connection->evalsha('return false', 0));
    }

    public function testEvalRebuildsTheClientWhenTheScriptErrorWasCausedByALostConnection()
    {
        $client = new FakePhpRedisClient;
        $client->evalResult = false;
        $client->errorOnEval = "READONLY You can't write against a read only replica.";

        $rebuilt = new FakePhpRedisClient;

        $connection = new PhpRedisConnection($client, fn () => $rebuilt);

        try {
            $connection->eval('return 1', 0);

            $this->fail('The script error should have been thrown.');
        } catch (RedisException) {
            //
        }

        $this->assertSame($rebuilt, $connection->client());
    }

    public function testEvalMayStillBeCalledOnAMockedConnection()
    {
        $connection = Mockery::mock(PhpRedisConnection::class);
        $connection->expects('command')->with('eval', ['return 1', [], 0])->andReturn(1);

        $this->assertSame(1, $connection->eval('return 1', 0));
    }

    public function testEvalShaLoadsTheScriptOnEveryMasterOfACluster()
    {
        $client = Mockery::mock(RedisCluster::class);
        $client->expects('clearLastError')->andReturn(true);
        $client->expects('_masters')->andReturn([['127.0.0.1', 6379], ['127.0.0.1', 6380]]);
        $client->expects('script')->with(['127.0.0.1', 6379], 'load', 'return 1')->andReturn('sha');
        $client->expects('script')->with(['127.0.0.1', 6380], 'load', 'return 1')->andReturn('sha');
        $client->expects('evalsha')->with('sha', [], 0)->andReturn(1);

        $connection = new PhpRedisClusterConnection($client);

        $this->assertSame(1, $connection->evalsha('return 1', 0));
    }

    public function testEvalShaThrowsTheScriptLoadErrorOnACluster()
    {
        $client = Mockery::mock(RedisCluster::class);
        $client->expects('clearLastError')->andReturn(true);
        $client->expects('_masters')->andReturn([['127.0.0.1', 6379]]);
        $client->expects('script')->with(['127.0.0.1', 6379], 'load', 'return 1')->andReturn(false);
        $client->expects('getLastError')->andReturn('ERR Lua redis lib command arguments must be strings or integers');
        $client->expects('evalsha')->never();

        $connection = new PhpRedisClusterConnection($client);

        $this->expectException(RedisException::class);
        $this->expectExceptionMessage('ERR Lua redis lib command arguments must be strings or integers');

        $connection->evalsha('return 1', 0);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}

/**
 * A stand-in for the phpredis client.
 *
 * This deliberately does not extend \Redis: the parent would be resolved while the file is
 * being included, before the extension requirement above can skip anything, so the whole
 * suite would die with a fatal error wherever the extension is not installed...
 */
class FakePhpRedisClient
{
    public mixed $evalResult = null;

    public ?string $errorOnEval = null;

    public ?string $lastError = null;

    public bool $lastErrorCleared = false;

    public function eval(string $script, array $args = [], int $num_keys = 0): mixed
    {
        $this->lastError = $this->evalResult === false ? $this->errorOnEval : null;

        return $this->evalResult;
    }

    public function evalsha(string $sha1, array $args = [], int $num_keys = 0): mixed
    {
        return $this->eval($sha1, $args, $num_keys);
    }

    public function script(string $command, mixed ...$args): mixed
    {
        return 'e0e1f9fabfc9d4800c877a703b823ac0578ff8db';
    }

    public function clearLastError(): bool
    {
        $this->lastError = null;
        $this->lastErrorCleared = true;

        return true;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
