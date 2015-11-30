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

        $dispatcher->dispatch(new BusDispatcherBasicCommand);
    }
}

class BusInjectionStub {}

class BusDispatcherBasicCommand {
    public $name;
    public function __construct($name = null)
    {
        $this->name = $name;
    }
    public function handle(BusInjectionStub $stub)
    {
        //
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
