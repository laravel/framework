<?php

namespace Illuminate\Tests\Log;

use Mockery as m;
use RuntimeException;
use Illuminate\Log\Logger;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class LogLoggerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testMethodsPassErrorAdditionsToLoggerInterface()
    {
        $writer = new Logger($logger = m::mock(LoggerInterface::class));
        $logger->shouldReceive('error')->once()->with('foo', []);

        $writer->error('foo');
    }

    public function testLoggerFiresEventsDispatcher()
    {
        $writer = new Logger($logger = m::mock(LoggerInterface::class), $events = new Dispatcher);
        $logger->shouldReceive('error')->once()->with('foo', []);

        $events->listen(MessageLogged::class, function ($event) {
            $_SERVER['__log.level'] = $event->level;
            $_SERVER['__log.message'] = $event->message;
            $_SERVER['__log.context'] = $event->context;
        });

        $writer->error('foo');
        $this->assertEquals('error', $_SERVER['__log.level'] ?? 'NOT SET');
        $this->assertEquals('foo', $_SERVER['__log.message'] ?? 'NOT SET');
        $this->assertEquals([], $_SERVER['__log.context'] ?? 'NOT SET');
        unset($_SERVER['__log.message'], $_SERVER['__log.level'], $_SERVER['__log.context']);
    }

    public function testListenShortcutFailsWithNoDispatcher()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Events dispatcher has not been set.');

        $writer = new Logger($logger = m::mock(LoggerInterface::class));
        $writer->listen(function () {
            //
        });
    }

    public function testListenShortcut()
    {
        $writer = new Logger($logger = m::mock(LoggerInterface::class), $events = m::mock(DispatcherContract::class));

        $callback = function () {
            return 'success';
        };
        $events->shouldReceive('listen')->with(MessageLogged::class, $callback)->once();

        $writer->listen($callback);
    }
}
