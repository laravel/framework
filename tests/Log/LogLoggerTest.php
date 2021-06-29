<?php

namespace Illuminate\Tests\Log;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Log\Logger;
use Mockery as m;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LogLoggerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testMethodsPassErrorAdditionsToMonolog()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $monolog->shouldReceive('error')->once()->with('foo', []);

        $writer->error('foo');
    }

    public function testContextIsAddedToAllSubsequentLogs()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $writer->withContext(['bar' => 'baz']);

        $monolog->shouldReceive('error')->once()->with('foo', ['bar' => 'baz']);

        $writer->error('foo');
    }

    public function testNestedContextOverrides()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $writer->withContext(['bar' => ['baz' => 'qux', 'baz2' => 'qux2']]);
        $writer->withContext(['bar' => ['baz' => 'quux']]);

        $monolog->shouldReceive('error')->once()->with('foo', ['bar' => ['baz' => 'quux', 'baz2' => 'qux2']]);

        $writer->error('foo');
    }

    public function testNestedLocalContextOverrides()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $writer->withContext(['bar' => ['baz' => 'qux', 'baz2' => 'qux2']]);

        $monolog->shouldReceive('error')->once()->with('foo', ['bar' => ['baz' => 'quux', 'baz2' => 'qux2']]);

        $writer->error('foo', ['bar' => ['baz' => 'quux']]);
    }

    public function testRetrieveAndOverrideCurrentContext()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $writer->withContext(['bar' => ['baz' => 'qux', 'baz2' => 'qux2']]);

        $this->assertEquals(['bar' => ['baz' => 'quux', 'baz2' => 'qux2']], $writer->getContext(['bar' => ['baz' => 'quux']]));
    }

    public function testRetrievingContextWithOverrideDoesNotApplyToSubsequentLogs()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class));
        $writer->withContext(['bar' => ['baz' => 'qux', 'baz2' => 'qux2']]);

        $writer->getContext(['bar' => ['baz' => 'quux']]);

        $monolog->shouldReceive('error')->once()->with('foo', ['bar' => ['baz' => 'qux', 'baz2' => 'qux2']]);

        $writer->error('foo');
    }

    public function testLoggerFiresEventsDispatcher()
    {
        $writer = new Logger($monolog = m::mock(Monolog::class), $events = new Dispatcher);
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
}
