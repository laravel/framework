<?php

namespace Illuminate\Tests\Redis;

use Exception;
use Illuminate\Events\Dispatcher;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Redis\Events\CommandFailed;
use Illuminate\Support\Testing\Fakes\EventFake;
use Mockery;
use PHPUnit\Framework\TestCase;
use Redis;

class RedisEventsTest extends TestCase
{
    public function testCommandFailedEventIsDispatched()
    {
        $exception = new Exception('Test exception');

        $client = Mockery::mock(Redis::class);
        $client->expects('get')->with('key')->andThrow($exception);

        $events = new EventFake(new Dispatcher);

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        try {
            $connection->command('get', ['key']);
        } catch (Exception) {
            // Expected exception
        }

        $events->assertDispatchedOnce(CommandFailed::class);
        $events->assertDispatched(CommandFailed::class, function ($event) use ($exception) {
            return $event->command === 'get'
                && $event->parameters === ['key']
                && $event->exception === $exception;
        });
    }

    public function testCommandExecutedEventIsNotDispatchedWhenCommandFails()
    {
        $exception = new Exception('Test exception');

        $client = Mockery::mock(Redis::class);
        $client->expects('get')->with('key')->andThrow($exception);

        $events = new EventFake(new Dispatcher);

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        try {
            $connection->command('get', ['key']);
        } catch (Exception) {
            // Expected exception
        }

        $events->assertDispatchedOnce(CommandFailed::class);
        $events->assertNotDispatched(CommandExecuted::class);
    }

    public function testLuaAclErrorsAreReportedAsCommandFailures()
    {
        $error_message = 'ERR ACL failure in script: user has no permissions to run the rpush command';
        $client = new PhpRedisConnectionTestClient([$error_message]);

        $events = new EventFake(new Dispatcher);

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        try {
            $connection->command('eval', ['return redis.call("rpush")', [], 0]);
        } catch (\RedisException $exception) {
            $this->assertSame($error_message, $exception->getMessage());
        }

        $events->assertDispatchedOnce(CommandFailed::class, function ($event) use ($error_message) {
            return $event->command === 'eval' && $event->exception->getMessage() === $error_message;
        });
        $events->assertNotDispatched(CommandExecuted::class);
    }

    public function testLuaNilAndFalseResultsRemainSuccessfulAndStaleErrorsAreCleared()
    {
        $error_message = 'ERR ACL failure';
        $client = new PhpRedisConnectionTestClient([$error_message, null, null]);

        $events = new EventFake(new Dispatcher);

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        try {
            $connection->command('eval', ['return redis.call("rpush")', [], 0]);
            $this->fail('An ACL error should be thrown.');
        } catch (\RedisException $exception) {
            $this->assertSame($error_message, $exception->getMessage());
        }

        $this->assertFalse($connection->command('eval', ['return nil', [], 0]));
        $this->assertFalse($connection->command('eval', ['return false', [], 0]));

        $events->assertDispatchedTimes(CommandExecuted::class, 2);
        $events->assertDispatchedOnce(CommandFailed::class);
    }

    public function testEvalshaAclErrorsAreReported()
    {
        $error_message = 'ERR ACL failure in script: user has no permissions to run the rpush command';
        $client = new PhpRedisConnectionTestClient([$error_message]);

        $connection = new PhpRedisConnection($client);

        try {
            $connection->command('evalsha', ['script-sha', [], 0]);
            $this->fail('An ACL error should be thrown.');
        } catch (\RedisException $exception) {
            $this->assertSame($error_message, $exception->getMessage());
        }
    }

    public function testCommandFailedEventContainsConnectionName()
    {
        $exception = new Exception('Test exception');

        $client = Mockery::mock(Redis::class);
        $client->expects('get')->with('key')->andThrow($exception);

        $events = new EventFake(new Dispatcher);

        $connection = new PhpRedisConnection($client);
        $connection->setName('test-connection');
        $connection->setEventDispatcher($events);

        try {
            $connection->command('get', ['key']);
        } catch (Exception) {
            // Expected exception
        }

        $events->assertDispatched(CommandFailed::class, function ($event) {
            return $event->connectionName === 'test-connection';
        });
    }

    public function testListenForFailuresRegistersCallback()
    {
        $client = Mockery::mock(Redis::class);

        $events = new Dispatcher;

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        $connection->listenForFailures(function () {
            // callback
        });

        $this->assertTrue($events->hasListeners(CommandFailed::class));
    }
}

class PhpRedisConnectionTestClient
{
    private $errors;

    private $last_error = 'ERR stale error';

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function clearLastError()
    {
        $this->last_error = null;
    }

    public function eval($script, $arguments, $number_of_keys)
    {
        $error = array_shift($this->errors);

        if ($error !== null) {
            $this->last_error = $error;
        }

        return false;
    }

    public function evalsha($script, $arguments, $number_of_keys)
    {
        $error = array_shift($this->errors);

        if ($error !== null) {
            $this->last_error = $error;
        }

        return false;
    }

    public function getLastError()
    {
        return $this->last_error;
    }
}
