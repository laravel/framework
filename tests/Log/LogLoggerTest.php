<?php

namespace Illuminate\Tests\Log;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Log\Logger;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LogLoggerTest extends TestCase
{
    public function testMethodsPassErrorAdditionsToMonolog()
    {
        $monolog = new Monolog('test');
        $monolog->pushHandler($handler = new TestHandler);
        $writer = new Logger($monolog);

        $writer->error('foo');

        $this->assertTrue($handler->hasErrorThatContains('foo'));
        $this->assertSame([], $handler->getRecords()[0]->context);
    }

    public function testContextIsAddedToAllSubsequentLogs()
    {
        $monolog = new Monolog('test');
        $monolog->pushHandler($handler = new TestHandler);
        $writer = new Logger($monolog);
        $writer->withContext(['bar' => 'baz']);

        $writer->error('foo');

        $this->assertSame(['bar' => 'baz'], $handler->getRecords()[0]->context);
    }

    public function testContextIsFlushed()
    {
        $monolog = new Monolog('test');
        $monolog->pushHandler($handler = new TestHandler);
        $writer = new Logger($monolog);
        $writer->withContext(['bar' => 'baz']);
        $writer->withoutContext();

        $writer->error('foo');

        $this->assertSame([], $handler->getRecords()[0]->context);
    }

    public function testContextKeysCanBeRemovedForSubsequentLogs()
    {
        $monolog = new Monolog('test');
        $monolog->pushHandler($handler = new TestHandler);
        $writer = new Logger($monolog);
        $writer->withContext(['bar' => 'baz', 'forget' => 'me']);
        $writer->withoutContext(['forget']);

        $writer->error('foo');

        $this->assertSame(['bar' => 'baz'], $handler->getRecords()[0]->context);
    }

    public function testLoggerFiresEventsDispatcher()
    {
        $monolog = new Monolog('test');
        $monolog->pushHandler(new TestHandler);
        $writer = new Logger($monolog, $events = new Dispatcher);

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
        $this->assertSame([], $_SERVER['__log.context']);
        unset($_SERVER['__log.context']);
    }

    public function testListenShortcutFailsWithNoDispatcher()
    {
        $this->expectExceptionObject(new RuntimeException('Events dispatcher has not been set.'));

        $writer = new Logger(new Monolog('test'));
        $writer->listen(function () {
            //
        });
    }

    public function testListenShortcut()
    {
        $events = new Dispatcher;
        $writer = new Logger(new Monolog('test'), $events);

        $called = false;
        $writer->listen(function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($events->hasListeners(MessageLogged::class));

        $events->dispatch(new MessageLogged('info', 'foo', []));

        $this->assertTrue($called);
    }

    public function testComplexContextManipulation()
    {
        $monolog = new Monolog('test');
        $monolog->pushHandler($handler = new TestHandler);
        $writer = new Logger($monolog);

        $writer->withContext(['user_id' => 123, 'action' => 'login']);
        $writer->withContext(['ip' => '127.0.0.1', 'timestamp' => '1986-10-29']);
        $writer->withoutContext(['timestamp']);

        $writer->info('User action');

        $this->assertSame([
            'user_id' => 123,
            'action' => 'login',
            'ip' => '127.0.0.1',
        ], $handler->getRecords()[0]->context);
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
