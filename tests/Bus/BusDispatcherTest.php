<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BusDispatcherTest extends TestCase
{
    public function testCommandsThatShouldQueueIsQueued()
    {
        $container = new Container;
        $queueRoutes = m::mock();
        $container->instance('queue.routes', $queueRoutes);
        $queueRoutes->expects('getQueue')->andReturn(null);
        $queueRoutes->expects('getConnection')->andReturn(null);
        Container::setInstance($container);
        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock(Queue::class);
            $mock->expects('push');

            return $mock;
        });

        $dispatcher->dispatch(m::mock(ShouldQueue::class));

        Container::setInstance(null);
    }

    public function testCommandsThatShouldQueueIsQueuedUsingCustomHandler()
    {
        $container = new Container;
        $queueRoutes = m::mock();
        $container->instance('queue.routes', $queueRoutes);
        $queueRoutes->expects('getConnection')->andReturn(null);
        Container::setInstance($container);
        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock(Queue::class);
            $mock->expects('push');

            return $mock;
        });

        $dispatcher->dispatch(new BusDispatcherTestCustomQueueCommand);

        Container::setInstance(null);
    }

    public function testCommandsThatShouldQueueIsQueuedUsingCustomQueueAndDelay()
    {
        $container = new Container;
        $queueRoutes = m::mock();
        $container->instance('queue.routes', $queueRoutes);
        $queueRoutes->expects('getConnection')->andReturn(null);
        Container::setInstance($container);
        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock(Queue::class);
            $mock->expects('later')->with(10, m::type(BusDispatcherTestSpecificQueueAndDelayCommand::class), '', 'foo');

            return $mock;
        });

        $dispatcher->dispatch(new BusDispatcherTestSpecificQueueAndDelayCommand);

        Container::setInstance(null);
    }

    public function testCommandsAreDispatchedWithQueueRoute()
    {
        Container::setInstance($container = new Container);
        $queueRoutes = m::mock();
        $container->instance('queue.routes', $queueRoutes);
        $queueRoutes->expects('getQueue')->andReturn('high-priority');
        $queueRoutes->expects('getConnection')->andReturn(null);

        $mock = m::mock(Queue::class);
        $mock->expects('push')->with(BusDispatcherQueueable::class, '', 'high-priority');

        $dispatcher = new Dispatcher($container, function () use ($mock) {
            return $mock;
        });

        $dispatcher->dispatch(new BusDispatcherQueueable);

        Container::setInstance(null);
    }

    public function testDispatchNowShouldNeverQueue()
    {
        $container = new Container;
        $mock = m::mock(Queue::class);
        $mock->shouldReceive('push')->never();
        $dispatcher = new Dispatcher($container, function () use ($mock) {
            return $mock;
        });

        $dispatcher->dispatch(new BusDispatcherBasicCommand);
    }

    public function testDispatcherCanDispatchStandAloneHandler()
    {
        $container = new Container;
        $mock = m::mock(Queue::class);
        $dispatcher = new Dispatcher($container, function () use ($mock) {
            return $mock;
        });

        $dispatcher->map([StandAloneCommand::class => StandAloneHandler::class]);

        $response = $dispatcher->dispatch(new StandAloneCommand);

        $this->assertInstanceOf(StandAloneCommand::class, $response);
    }

    public function testOnConnectionOnJobWhenDispatching()
    {
        Container::setInstance($container = new Container);
        $container->singleton('config', function () {
            return new Config([
                'queue' => [
                    'default' => 'null',
                    'connections' => [
                        'null' => ['driver' => 'null'],
                    ],
                ],
            ]);
        });
        $queueRoutes = m::mock();
        $container->instance('queue.routes', $queueRoutes);
        $queueRoutes->expects('getQueue')->andReturn(null);
        Container::setInstance($container);

        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock(Queue::class);
            $mock->expects('push');

            return $mock;
        });

        $job = (new ShouldNotBeDispatched)->onConnection('null');

        $dispatcher->dispatch($job);

        Container::setInstance(null);
    }

    public function testDispatchBulk()
    {
        $container = new Container;
        $queueRoutes = m::mock();
        $container->instance('queue.routes', $queueRoutes);
        $queueRoutes->expects('getQueue')->times(2)->andReturn(null);
        $queueRoutes->expects('getConnection')->times(3)->andReturn(null);
        Container::setInstance($container);

        $mock = m::mock(Queue::class);
        $mock->expects('bulk')->with(m::on(fn ($jobs) => count($jobs) === 2), '', null);
        $mock->expects('bulk')->with(m::on(fn ($jobs) => count($jobs) === 1), '', 'high');

        $dispatcher = new Dispatcher($container, fn () => $mock);

        $dispatcher->bulk([
            new BusDispatcherQueueable,
            new BusDispatcherQueueable,
            new BusDispatcherTestSpecificQueueCommand,
        ]);

        Container::setInstance(null);
    }
}

class BusInjectionStub
{
    //
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
        //
    }
}

class BusDispatcherTestCustomQueueCommand implements ShouldQueue
{
    public function queue($queue, $command)
    {
        $queue->push($command);
    }
}

class BusDispatcherTestSpecificQueueAndDelayCommand implements ShouldQueue
{
    public $queue = 'foo';
    public $delay = 10;
}

class BusDispatcherTestSpecificQueueCommand implements ShouldQueue
{
    public $queue = 'high';
}

class BusDispatcherQueueable implements ShouldQueue
{
    use Queueable;
}

class StandAloneCommand
{
    //
}

class StandAloneHandler
{
    public function handle(StandAloneCommand $command)
    {
        return $command;
    }
}

class ShouldNotBeDispatched implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function handle()
    {
        throw new RuntimeException('This should not be run');
    }
}
