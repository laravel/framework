<?php

namespace Illuminate\Tests\Log;

use Mockery as m;
use Illuminate\Log\Logger;
use PHPUnit\Framework\TestCase;

class LogWriterTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMethodsPassErrorAdditionsToMonolog()
    {
        $writer = new Logger($monolog = m::mock('Monolog\Logger'));
        $monolog->shouldReceive('error')->once()->with('foo', []);

        $writer->error('foo');
    }

    public function testWriterFiresEventsDispatcher()
    {
        $writer = new Logger($monolog = m::mock('Monolog\Logger'), $events = new \Illuminate\Events\Dispatcher);
        $monolog->shouldReceive('error')->once()->with('foo', []);

        $events->listen(\Illuminate\Log\Events\MessageLogged::class, function ($event) {
            $_SERVER['__log.level'] = $event->level;
            $_SERVER['__log.message'] = $event->message;
            $_SERVER['__log.context'] = $event->context;
        });

        $writer->error('foo');
        $this->assertTrue(isset($_SERVER['__log.level']));
        $this->assertEquals('error', $_SERVER['__log.level']);
        unset($_SERVER['__log.level']);
        $this->assertTrue(isset($_SERVER['__log.message']));
        $this->assertEquals('foo', $_SERVER['__log.message']);
        unset($_SERVER['__log.message']);
        $this->assertTrue(isset($_SERVER['__log.context']));
        $this->assertEquals([], $_SERVER['__log.context']);
        unset($_SERVER['__log.context']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Events dispatcher has not been set.
     */
    public function testListenShortcutFailsWithNoDispatcher()
    {
        $writer = new Logger($monolog = m::mock('Monolog\Logger'));
        $writer->listen(function () {
        });
    }

    public function testListenShortcut()
    {
        $writer = new Logger($monolog = m::mock('Monolog\Logger'), $events = m::mock('Illuminate\Contracts\Events\Dispatcher'));

        $callback = function () {
            return 'success';
        };
        $events->shouldReceive('listen')->with(\Illuminate\Log\Events\MessageLogged::class, $callback)->once();

        $writer->listen($callback);
    }
}
