<?php

use Mockery as m;
use Illuminate\Bus\Dispatcher;
use Illuminate\Container\Container;

class BusDispatcherTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCommandsThatShouldQueueIsQueued()
    {
        $container = new Container;
        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock('Illuminate\Contracts\Queue\Queue');
            $mock->shouldReceive('push')->once();

            return $mock;
        });

        $dispatcher->dispatch(m::mock('Illuminate\Contracts\Queue\ShouldQueue'));
    }

    public function testCommandsThatShouldQueueIsQueuedUsingCustomHandler()
    {
        $container = new Container;
        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock('Illuminate\Contracts\Queue\Queue');
            $mock->shouldReceive('push')->once();

            return $mock;
        });

        $dispatcher->dispatch(new BusDispatcherTestCustomQueueCommand);
    }

    public function testCommandsThatShouldQueueIsQueuedUsingCustomQueueAndDelay()
    {
        $container = new Container;
        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock('Illuminate\Contracts\Queue\Queue');
            $mock->shouldReceive('laterOn')->once()->with('foo', 10, m::type('BusDispatcherTestSpecificQueueAndDelayCommand'));

            return $mock;
        });

        $dispatcher->dispatch(new BusDispatcherTestSpecificQueueAndDelayCommand);
    }

    public function testDispatchNowShouldNeverQueue()
    {
        $container = new Container;
        $mock = m::mock('Illuminate\Contracts\Queue\Queue');
        $mock->shouldReceive('push')->never();
        $dispatcher = new Dispatcher($container, function () use ($mock) {
            return $mock;
        });

        $result = $dispatcher->dispatch(new BusDispatcherBasicCommand('hello'));

        $this->assertEquals('hello', $result);
    }

    public function testDispatchNowWithHandler()
    {
        $container = new Container;
        $mock = m::mock('Illuminate\Contracts\Queue\Queue');
        $mock->shouldReceive('push')->never();
        $dispatcher = new Dispatcher($container, function () use ($mock) {
            return $mock;
        });

        $dispatcher->mapUsing(function ($command) {
            return get_class($command).'Handler';
        });

        $result = $dispatcher->dispatch(new BusDispatcherCommand(123));

        $this->assertEquals(165, $result);
    }
}

class BusInjectionStub
{
}

class BusDispatcherBasicCommand
{
    public $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function handle(BusInjectionStub $stub)
    {
        return $this->name;
    }
}

class BusDispatcherCommand
{
    public $foo;

    public function __construct($foo = null)
    {
        $this->foo = $foo;
    }
}

class BusDispatcherCommandHandler
{
    public function handle(BusDispatcherCommand $command)
    {
        return $command->foo + 42;
    }
}

class BusDispatcherTestCustomQueueCommand implements Illuminate\Contracts\Queue\ShouldQueue
{
    public function queue($queue, $command)
    {
        $queue->push($command);
    }
}

class BusDispatcherTestSpecificQueueAndDelayCommand implements Illuminate\Contracts\Queue\ShouldQueue
{
    public $queue = 'foo';
    public $delay = 10;
}
