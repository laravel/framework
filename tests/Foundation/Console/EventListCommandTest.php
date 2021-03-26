<?php

namespace Illuminate\Tests\Foundation\Console;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\OutputStyle;
use Illuminate\Events\SpyDispatcher;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Input\ArrayInput;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\EventListCommand;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class EventListCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function testRoutine(array $inputParams, array $providerClasses, string $expectedOutput)
    {
        $input = new ArrayInput($inputParams);
        $output = new BufferedOutput();

        $container = m::mock(Application::class);

        $container->shouldReceive('call');
        $container->shouldReceive('eventsAreCached')->andReturn(false);

        $container->shouldReceive('getProviders')
            ->with(EventServiceProvider::class)
            ->andReturn(array_map(function ($providerClass) use ($container) {
                return new $providerClass($container);
            }, $providerClasses));

        $container->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(new OutputStyle($input, $output));

        $container->shouldReceive('make')
            ->with(SpyDispatcher::class)
            ->andReturn(new SpyDispatcher($container));

        $container->shouldReceive('make')
            ->with(TestSubscriber::class)
            ->andReturn(new TestSubscriber());

        $command = new EventListCommand();
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();

        $actualOutput = $output->fetch();
        $actualLines = array_filter(explode(PHP_EOL, $actualOutput));
        $expectedLines = array_filter(explode(PHP_EOL, $expectedOutput));

        $this->assertEquals($expectedLines, $actualLines);
    }

    public function testWithNoEvent()
    {
        $this->testRoutine(
            [],
            [EventServiceProvider::class],
            "Your application doesn't have any events matching the given criteria.");
    }

    public function testWithMultipleEvents()
    {
        $this->testRoutine(
            [],
            [TestMultipleEventsServiceProvider::class, EventServiceProvider::class],
            <<<OUTPUT
+------------+------------------------------+
| Event      | Listeners                    |
+------------+------------------------------+
| Some\Event | Some\Listener\FirstListener  |
|            | Some\Listener\SecondListener |
| Some\Other | Some\Listener\ThirdListener  |
+------------+------------------------------+
OUTPUT
        );
    }

    public function testWithFilteredMultipleEvents()
    {
        $this->testRoutine(
            ['--event' => 'Other'],
            [TestMultipleEventsServiceProvider::class, TestClosureServiceProvider::class],
            <<<OUTPUT
+------------+-----------------------------+
| Event      | Listeners                   |
+------------+-----------------------------+
| Some\Other | Some\Listener\ThirdListener |
+------------+-----------------------------+
OUTPUT
        );
    }

    public function testWithEventSubscribe()
    {
        $this->testRoutine(
            [],
            [TestSubscriberServiceProvider::class],
            <<<OUTPUT
+-------------------------------+---------------------------------------------------------------------+
| Event                         | Listeners                                                           |
+-------------------------------+---------------------------------------------------------------------+
| Illuminate\Auth\Events\Login  | Illuminate\Tests\Foundation\Console\TestSubscriber@handleUserLogin  |
| Illuminate\Auth\Events\Logout | Illuminate\Tests\Foundation\Console\TestSubscriber@handleUserLogout |
+-------------------------------+---------------------------------------------------------------------+
OUTPUT
        );
    }

    public function testWithClosure()
    {
        $this->testRoutine(
            [],
            [TestClosureServiceProvider::class],
            <<<OUTPUT
+-------+-----------+
| Event | Listeners |
+-------+-----------+
| test  | Closure   |
+-------+-----------+
OUTPUT
        );
    }

    public function testWithWildCards()
    {
        $this->testRoutine(
            [],
            [TestWildCardServiceProvider::class],
            <<<OUTPUT
+--------+-----------+
| Event  | Listeners |
+--------+-----------+
| test.* | Closure   |
+--------+-----------+
OUTPUT
        );
    }
}

class TestMultipleEventsServiceProvider extends EventServiceProvider
{
    protected $listen = [
        \Some\Event::class => [
            \Some\Listener\FirstListener::class,
            \Some\Listener\SecondListener::class,
        ],

        \Some\Other::class => [
            \Some\Listener\ThirdListener::class,
        ],
    ];
}

class TestSubscriberServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        TestSubscriber::class,
    ];
}

class TestClosureServiceProvider extends EventServiceProvider
{
    public function boot()
    {
        parent::boot();

        Event::listen('test', function () {
            //
        });
    }
}

class TestWildCardServiceProvider extends EventServiceProvider
{
    public function boot()
    {
        parent::boot();

        Event::listen('test.*', function () {
            //
        });
    }
}

class TestSubscriber
{
    public function handleUserLogin($event)
    {
        //
    }

    public function handleUserLogout($event)
    {
        //
    }

    public function subscribe($events)
    {
        $events->listen(
            'Illuminate\Auth\Events\Login',
            static::class.'@handleUserLogin'
        );

        $events->listen(
            'Illuminate\Auth\Events\Logout',
            static::class.'@handleUserLogout'
        );
    }
}
