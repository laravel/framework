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
        $dispatcher->mapUsing(function () { return 'Handler@handle'; });

        $result = $dispatcher->dispatch(new BusDispatcherTestBasicCommand);
        $this->assertEquals('foo', $result);
    }

    public function testCommandsThatShouldQueueIsQueued()
    {
        $container = new Container;

        $command = m::mock('Illuminate\Contracts\Queue\ShouldQueue');

        $queue = m::mock('Illuminate\Contracts\Queue\Queue');
        $queue->shouldReceive('push')->once()->with($command);

        $manager = m::mock('Illuminate\Queue\QueueManager');
        $manager->shouldReceive('connection')->once()->with(null)->andReturn($queue);

        $dispatcher = new Dispatcher($container, $manager);

        $dispatcher->dispatch($command);
    }

    public function testCommandsThatShouldQueueIsQueuedUsingCustomQueue()
    {
        $container = new Container;

        $command = new BusDispatcherTestSpecificQueueCommand;

        $queue = m::mock('Illuminate\Contracts\Queue\Queue');
        $queue->shouldReceive('pushOn')->once()->with('foo', $command);

        $manager = m::mock('Illuminate\Queue\QueueManager');
        $manager->shouldReceive('connection')->once()->with('bar')->andReturn($queue);

        $dispatcher = new Dispatcher($container, $manager);

        $dispatcher->dispatch($command);
    }

    public function testCommandsThatShouldQueueIsQueuedUsingCustomQueueAndDelay()
    {
        $container = new Container;

        $command = new BusDispatcherTestSpecificQueueAndDelayCommand;

        $queue = m::mock('Illuminate\Contracts\Queue\Queue');
        $queue->shouldReceive('laterOn')->once()->with('foo', 10, $command);

        $manager = m::mock('Illuminate\Queue\QueueManager');
        $manager->shouldReceive('connection')->once()->with('bar')->andReturn($queue);

        $dispatcher = new Dispatcher($container, $manager);

        $dispatcher->dispatch($command);
    }

    public function testHandlersThatShouldQueueIsQueued()
    {
        $container = new Container;

        $command = new BusDispatcherTestBasicCommand;

        $queue = m::mock('Illuminate\Contracts\Queue\Queue');
        $queue->shouldReceive('push')->once()->with($command);

        $manager = m::mock('Illuminate\Queue\QueueManager');
        $manager->shouldReceive('connection')->once()->with(null)->andReturn($queue);

        $dispatcher = new Dispatcher($container, $manager);
        $dispatcher->mapUsing(function () { return 'BusDispatcherTestQueuedHandler@handle'; });

        $dispatcher->dispatch($command);
    }

    public function testDispatchNowShouldNeverQueue()
    {
        $container = new Container;
        $handler = m::mock('StdClass');
        $handler->shouldReceive('handle')->once()->andReturn('foo');
        $container->instance('Handler', $handler);
        $dispatcher = new Dispatcher($container);
        $dispatcher->mapUsing(function () { return 'Handler@handle'; });

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
        $dispatcher->mapUsing(function () { return 'Handler@handle'; });

        $dispatcher->dispatch(new BusDispatcherTestBasicCommand, function ($handler) { $handler->after(); });
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

class BusDispatcherTestSpecificQueueCommand implements Illuminate\Contracts\Queue\ShouldQueue
{
    public $queue;

    public function __construct()
    {
        $this->queue = new Illuminate\Bus\QueuingConfiguration('foo', 'bar', null);
    }
}

class BusDispatcherTestSpecificQueueAndDelayCommand implements Illuminate\Contracts\Queue\ShouldQueue
{
    public $queue;

    public function __construct()
    {
        $this->queue = new Illuminate\Bus\QueuingConfiguration('foo', 'bar', 10);
    }
}
