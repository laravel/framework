<?php

namespace Illuminate\Tests\Bus;

use Mockery as m;
use Illuminate\Bus\Dispatcher;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;

class BusDispatcherTest extends TestCase
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
            $mock->shouldReceive('laterOn')->once()->with('foo', 10, m::type('Illuminate\Tests\Bus\BusDispatcherTestSpecificQueueAndDelayCommand'));

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

    public function testDispatcherCanDispatchStandAloneHandler()
    {
        $container = new Container;
        $mock = m::mock('Illuminate\Contracts\Queue\Queue');
        $dispatcher = new Dispatcher($container, function () use ($mock) {
            return $mock;
        });

        $dispatcher->map([StandAloneCommand::class => StandAloneHandler::class]);

        $response = $dispatcher->dispatch(new StandAloneCommand);

        $this->assertInstanceOf(StandAloneCommand::class, $response);
    }

    public function testOnConnectionOnJobWhenDispatching()
    {
        $container = new Container;
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

        $dispatcher = new Dispatcher($container, function () {
            $mock = m::mock('Illuminate\Contracts\Queue\Queue');
            $mock->shouldReceive('push')->once();

            return $mock;
        });

        $job = (new ShouldNotBeDispatched)->onConnection('null');

        $dispatcher->dispatch($job);
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
        //
    }
}

class BusDispatcherTestCustomQueueCommand implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public function queue($queue, $command)
    {
        $queue->push($command);
    }
}

class BusDispatcherTestSpecificQueueAndDelayCommand implements \Illuminate\Contracts\Queue\ShouldQueue
{
    public $queue = 'foo';
    public $delay = 10;
}

class StandAloneCommand
{
}

class StandAloneHandler
{
    public function handle(StandAloneCommand $command)
    {
        return $command;
    }
}

class ShouldNotBeDispatched implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable,
        \Illuminate\Queue\InteractsWithQueue;

    public function handle()
    {
        throw new \RuntimeException('This should not be run');
    }
}
