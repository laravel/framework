<?php

namespace Illuminate\Tests\Redis;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Redis\Events\CommandFailed;
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

        $events = Mockery::mock(Dispatcher::class);
        $events->expects('dispatch')->with(Mockery::on(function ($event) use ($exception) {
            return $event instanceof CommandFailed
                && $event->command === 'get'
                && $event->parameters === ['key']
                && $event->exception === $exception;
        }));

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        $this->expectExceptionObject(new Exception('Test exception'));

        $connection->command('get', ['key']);
    }

    public function testCommandExecutedEventIsNotDispatchedWhenCommandFails()
    {
        $exception = new Exception('Test exception');

        $client = Mockery::mock(Redis::class);
        $client->expects('get')->with('key')->andThrow($exception);

        $events = Mockery::mock(Dispatcher::class);
        $events->expects('dispatch')->with(Mockery::type(CommandFailed::class));
        $events->shouldNotReceive('dispatch')->with(Mockery::type(CommandExecuted::class));

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        try {
            $connection->command('get', ['key']);
        } catch (Exception) {
            // Expected exception
        }
    }

    public function testCommandFailedEventContainsConnectionName()
    {
        $exception = new Exception('Test exception');

        $client = Mockery::mock(Redis::class);
        $client->expects('get')->with('key')->andThrow($exception);

        $events = Mockery::mock(Dispatcher::class);
        $events->expects('dispatch')->with(Mockery::on(function ($event) {
            return $event instanceof CommandFailed
                && $event->connectionName === 'test-connection';
        }));

        $connection = new PhpRedisConnection($client);
        $connection->setName('test-connection');
        $connection->setEventDispatcher($events);

        try {
            $connection->command('get', ['key']);
        } catch (Exception) {
            // Expected exception
        }
    }

    public function testListenForFailuresRegistersCallback()
    {
        $client = Mockery::mock(Redis::class);

        $events = Mockery::mock(Dispatcher::class);
        $events->expects('listen')->with(CommandFailed::class, Mockery::type('Closure'));

        $connection = new PhpRedisConnection($client);
        $connection->setEventDispatcher($events);

        $connection->listenForFailures(function () {
            // callback
        });
    }
}
