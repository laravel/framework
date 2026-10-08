<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connections\PhpRedisConnection;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Redis;
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
}

class FakePhpRedisClient extends Redis
{
    public mixed $evalResult = null;

    public ?string $errorOnEval = null;

    public ?string $lastError = null;

    public bool $lastErrorCleared = false;

    public function __construct()
    {
        //
    }

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
