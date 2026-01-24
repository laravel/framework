<?php

namespace Illuminate\Tests\Log;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Log\Logger;
use Mockery as m;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LogLoggerTest extends TestCase
{
    public function testMethodsPassErrorAdditionsToMonolog()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $monolog->shouldReceive('isHandling')->with('error')->andReturn(true);
        $monolog->shouldReceive('error')->once()->with('foo', []);

        $writer->error('foo');
    }

    public function testContextIsAddedToAllSubsequentLogs()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $writer->withContext(['bar' => 'baz']);

        $monolog->shouldReceive('isHandling')->with('error')->andReturn(true);
        $monolog->shouldReceive('error')->once()->with('foo', ['bar' => 'baz']);

        $writer->error('foo');
    }

    public function testContextIsFlushed()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $writer->withContext(['bar' => 'baz']);
        $writer->withoutContext();

        $monolog->shouldReceive('isHandling')->with('error')->andReturn(true);
        $monolog->expects('error')->with('foo', []);

        $writer->error('foo');
    }

    public function testContextKeysCanBeRemovedForSubsequentLogs()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $writer->withContext(['bar' => 'baz', 'forget' => 'me']);
        $writer->withoutContext(['forget']);

        $monolog->shouldReceive('isHandling')->with('error')->andReturn(true);
        $monolog->shouldReceive('error')->once()->with('foo', ['bar' => 'baz']);

        $writer->error('foo');
    }

    public function testLoggerFiresEventsDispatcher()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class), $events = new Dispatcher);
        $monolog->shouldReceive('isHandling')->with('error')->andReturn(true);
        $monolog->shouldReceive('error')->once()->with('foo', []);

        $events->listen(MessageLogged::class, function ($event) {
            $_SERVER['__log.level'] = $event->level;
            $_SERVER['__log.message'] = $event->message;
            $_SERVER['__log.context'] = $event->context;
        });

        $writer->error('foo');
        $this->assertTrue(isset($_SERVER['__log.level']));
        $this->assertSame('error', $_SERVER['__log.level']);
        unset($_SERVER['__log.level']);
        $this->assertTrue(isset($_SERVER['__log.message']));
        $this->assertSame('foo', $_SERVER['__log.message']);
        unset($_SERVER['__log.message']);
        $this->assertTrue(isset($_SERVER['__log.context']));
        $this->assertEquals([], $_SERVER['__log.context']);
        unset($_SERVER['__log.context']);
    }

    public function testListenShortcutFailsWithNoDispatcher()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Events dispatcher has not been set.');

        $writer = new Logger(m::mock(Monolog::class));
        $writer->listen(function () {
            //
        });
    }

    public function testListenShortcut()
    {
        $writer = new Logger(m::mock(Monolog::class), $events = m::mock(DispatcherContract::class));

        $callback = function () {
            return 'success';
        };
        $events->shouldReceive('listen')->with(MessageLogged::class, $callback)->once();

        $writer->listen($callback);
    }

    public function testComplexContextManipulation()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));

        $writer->withContext(['user_id' => 123, 'action' => 'login']);
        $writer->withContext(['ip' => '127.0.0.1', 'timestamp' => '1986-10-29']);
        $writer->withoutContext(['timestamp']);

        $monolog->shouldReceive('isHandling')->with('info')->andReturn(true);
        $monolog->shouldReceive('info')->once()->with('User action', [
            'user_id' => 123,
            'action' => 'login',
            'ip' => '127.0.0.1',
        ]);

        $writer->info('User action');
    }

    public function testSkipsSerializationWhenLogLevelNotHandled()
    {
        $monolog = new Monolog('test');
        $monolog->pushHandler(new TestHandler(Level::Error));

        $writer = new Logger($monolog);

        $arrayable = new class implements Arrayable
        {
            public bool $wasCalled = false;

            public function toArray(): array
            {
                $this->wasCalled = true;

                return ['serialized' => 'data'];
            }
        };

        $writer->debug($arrayable);

        $this->assertFalse($arrayable->wasCalled);
    }

    public function testSerializesWhenLogLevelIsHandled()
    {
        $monolog = new Monolog('test');
        $handler = new TestHandler(Level::Debug);
        $monolog->pushHandler($handler);

        $writer = new Logger($monolog);

        $arrayable = new class implements Arrayable
        {
            public bool $wasCalled = false;

            public function toArray(): array
            {
                $this->wasCalled = true;

                return ['serialized' => 'data'];
            }
        };

        $writer->debug($arrayable);

        $this->assertTrue($arrayable->wasCalled);
        $this->assertTrue($handler->hasDebugRecords());
    }
}
