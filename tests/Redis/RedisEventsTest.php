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
