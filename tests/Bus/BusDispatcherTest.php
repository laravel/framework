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

    public function testBasicDispatchingOfCommandsToHandlers()
    {
        $container = new Container;
        $handler = m::mock('StdClass');
        $handler->shouldReceive('handle')->once()->andReturn('foo');
        $container->instance('Handler', $handler);
        $dispatcher = new Dispatcher($container);
        $dispatcher->mapUsing(function () {
            return 'Handler@handle';
        });

        $result = $dispatcher->dispatch(new BusDispatcherTestBasicCommand);
        $this->assertEquals('foo', $result);
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

    public function testHandlersThatShouldQueueIsQueued()
    {
        $container = new Container;
        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock('Illuminate\Contracts\Queue\Queue');
            $mock->shouldReceive('push')->once();

            return $mock;
        });
        $dispatcher->mapUsing(function () {
            return 'BusDispatcherTestQueuedHandler@handle';
        });

        $dispatcher->dispatch(new BusDispatcherTestBasicCommand);
    }

    public function testDispatchNowShouldNeverQueue()
    {
        $container = new Container;
        $handler = m::mock('StdClass');
        $handler->shouldReceive('handle')->once()->andReturn('foo');
        $container->instance('Handler', $handler);
        $dispatcher = new Dispatcher($container);
        $dispatcher->mapUsing(function () {
            return 'Handler@handle';
        });

        $result = $dispatcher->dispatch(m::mock('Illuminate\Contracts\Queue\ShouldQueue'));
        $this->assertEquals('foo', $result);
    }

    public function testDispatchShouldCallAfterResolvingIfCommandNotQueued()
    {
        $container = new Container;
        $handler = m::mock('StdClass')->shouldIgnoreMissing();
        $handler->shouldReceive('after')->once();
        $container->instance('Handler', $handler);
        $dispatcher = new Dispatcher($container);
        $dispatcher->mapUsing(function () {
            return 'Handler@handle';
        });

        $dispatcher->dispatch(new BusDispatcherTestBasicCommand, function ($handler) {
            $handler->after();
        });
    }

    public function testDispatchingFromArray()
    {
        $instance = new Dispatcher(new Container);
        $result = $instance->dispatchFromArray('BusDispatcherTestSelfHandlingCommand', ['firstName' => 'taylor', 'lastName' => 'otwell']);
        $this->assertEquals('taylor otwell', $result);
    }

    public function testMarshallArguments()
    {
        $instance = new Dispatcher(new Container);
        $result = $instance->dispatchFromArray('BusDispatcherTestArgumentMapping', ['flag' => false, 'emptyString' => '']);
        $this->assertTrue($result);
    }
}

class BusDispatcherTestBasicCommand
{
}

class BusDispatcherTestArgumentMapping implements Illuminate\Contracts\Bus\SelfHandling
{
    public $flag, $emptyString;

    public function __construct($flag, $emptyString)
    {
        $this->flag = $flag;
        $this->emptyString = $emptyString;
    }

    public function handle()
    {
        return true;
    }
}

class BusDispatcherTestSelfHandlingCommand implements Illuminate\Contracts\Bus\SelfHandling
{
    public $firstName, $lastName;

    public function __construct($firstName, $lastName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function handle()
    {
        return $this->firstName.' '.$this->lastName;
    }
}

class BusDispatcherTestQueuedHandler implements Illuminate\Contracts\Queue\ShouldQueue
{
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
