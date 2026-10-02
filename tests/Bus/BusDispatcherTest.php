<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BusDispatcherTest extends TestCase
{
    public function testCommandsThatShouldQueueIsQueued()
    {
        $container = new Container;
        $container->instance('queue.routes', new QueueRoutes);
        Container::setInstance($container);
        $queue = new QueueFake($container);
        $dispatcher = new Dispatcher($container, fn () => $queue);

        $dispatcher->dispatch(new BusDispatcherQueueable);

        $queue->assertPushedOnce(BusDispatcherQueueable::class);

        Container::setInstance(null);
    }

    public function testCommandsThatShouldQueueIsQueuedUsingCustomHandler()
    {
        $container = new Container;
        $container->instance('queue.routes', new QueueRoutes);
        Container::setInstance($container);
        $queue = new QueueFake($container);
        $dispatcher = new Dispatcher($container, fn () => $queue);

        $dispatcher->dispatch(new BusDispatcherTestCustomQueueCommand);

        $queue->assertPushedOnce(BusDispatcherTestCustomQueueCommand::class);

        Container::setInstance(null);
    }

    public function testCommandsThatShouldQueueIsQueuedUsingCustomQueueAndDelay()
    {
        $container = new Container;
        $container->instance('queue.routes', new QueueRoutes);
        Container::setInstance($container);
        $dispatcher = new Dispatcher($container, function () {
            $mock = Mockery::mock(Queue::class);
            $mock->expects('later')->with(10, Mockery::type(BusDispatcherTestSpecificQueueAndDelayCommand::class), '', 'foo');

            return $mock;
        });

        $dispatcher->dispatch(new BusDispatcherTestSpecificQueueAndDelayCommand);

        Container::setInstance(null);
    }

    public function testCommandsAreDispatchedWithQueueRoute()
    {
        Container::setInstance($container = new Container);
        $queueRoutes = new QueueRoutes;
        $queueRoutes->set(BusDispatcherQueueable::class, 'high-priority');
        $container->instance('queue.routes', $queueRoutes);

        $queue = new QueueFake($container);
        $dispatcher = new Dispatcher($container, fn () => $queue);

        $dispatcher->dispatch(new BusDispatcherQueueable);

        $queue->assertPushedOn('high-priority', BusDispatcherQueueable::class);

        Container::setInstance(null);
    }

    public function testCommandsAreForwardedToConnectionByQueueName()
    {
        Container::setInstance($container = new Container);
        $queueRoutes = new QueueRoutes;
        $queueRoutes->forward('reports', 'processing', 'cloud');
        $container->instance('queue.routes', $queueRoutes);

        $queue = new QueueFake($container);
        $usedConnection = false;

        $dispatcher = new Dispatcher($container, function ($connection) use ($queue, &$usedConnection) {
            $usedConnection = $connection;

            return $queue;
        });

        $dispatcher->dispatch((new BusDispatcherQueueable)->onQueue('reports'));

        $this->assertSame('cloud', $usedConnection);
        $queue->assertPushedOn('reports', BusDispatcherQueueable::class);

        Container::setInstance(null);
    }

    public function testExplicitConnectionWinsOverForwardedQueue()
    {
        Container::setInstance($container = new Container);
        $queueRoutes = new QueueRoutes;
        $queueRoutes->forward('reports', 'processing', 'cloud');
        $container->instance('queue.routes', $queueRoutes);

        $mock = Mockery::mock(Queue::class);
        $mock->expects('push')->with(Mockery::type(BusDispatcherQueueable::class), '', 'reports');

        $usedConnection = false;

        $dispatcher = new Dispatcher($container, function ($connection) use ($mock, &$usedConnection) {
            $usedConnection = $connection;

            return $mock;
        });

        $dispatcher->dispatch((new BusDispatcherQueueable)->onConnection('redis')->onQueue('reports'));

        $this->assertSame('redis', $usedConnection);

        Container::setInstance(null);
    }

    public function testDispatchNowShouldNeverQueue()
    {
        $container = new Container;
        $queue = new QueueFake($container);
        $dispatcher = new Dispatcher($container, fn () => $queue);

        $dispatcher->dispatch(new BusDispatcherBasicCommand);

        $queue->assertNothingPushed();
    }

    public function testDispatcherCanDispatchStandAloneHandler()
    {
        $container = new Container;
        $queue = new QueueFake($container);
        $dispatcher = new Dispatcher($container, fn () => $queue);

        $dispatcher->map([StandAloneCommand::class => StandAloneHandler::class]);

        $response = $dispatcher->dispatch(new StandAloneCommand);

        $this->assertInstanceOf(StandAloneCommand::class, $response);
        $queue->assertNothingPushed();
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
        $container->instance('queue.routes', new QueueRoutes);
        Container::setInstance($container);

        $queue = new QueueFake($container);
        $dispatcher = new Dispatcher($container, fn () => $queue);

        $job = (new ShouldNotBeDispatched)->onConnection('null');

        $dispatcher->dispatch($job);

        $queue->assertPushedOnce(ShouldNotBeDispatched::class);

        Container::setInstance(null);
    }

    public function testDispatchBulk()
    {
        $container = new Container;
        $container->instance('queue.routes', new QueueRoutes);
        Container::setInstance($container);

        $mock = Mockery::mock(Queue::class);
        $mock->expects('bulk')->with(Mockery::on(fn ($jobs) => count($jobs) === 2), '', null);
        $mock->expects('bulk')->with(Mockery::on(fn ($jobs) => count($jobs) === 1), '', 'high');

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
