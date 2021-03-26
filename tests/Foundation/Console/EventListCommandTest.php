<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\SpyDispatcher;
use Illuminate\Foundation\Console\EventListCommand;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EventListCommandTest extends TestCase
{
    const DOES_NOT_HAVE_ANY_EVENTS = "Your application doesn't have any events matching the given criteria.".PHP_EOL;

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

        $this->assertEquals($expectedOutput, $output->fetch());
    }

    public function testWithNoEvent()
    {
        $this->testRoutine([], [EventServiceProvider::class], self::DOES_NOT_HAVE_ANY_EVENTS);
    }

    public function testWithMultipleEvents()
    {
        $this->testRoutine(
            [],
            [TestMultipleEventsServiceProvider::class, EventServiceProvider::class],
            '+------------+------------------------------+'.PHP_EOL.
            '| Event      | Listeners                    |'.PHP_EOL.
            '+------------+------------------------------+'.PHP_EOL.
            '| Some\Event | Some\Listener\FirstListener  |'.PHP_EOL.
            '|            | Some\Listener\SecondListener |'.PHP_EOL.
            '| Some\Other | Some\Listener\ThirdListener  |'.PHP_EOL.
            '+------------+------------------------------+'.PHP_EOL);
    }

    public function testWithFilteredMultipleEvents()
    {
        $this->testRoutine(
            ['--event' => 'Other'],
            [TestMultipleEventsServiceProvider::class, TestClosureServiceProvider::class],
            '+------------+-----------------------------+'.PHP_EOL.
            '| Event      | Listeners                   |'.PHP_EOL.
            '+------------+-----------------------------+'.PHP_EOL.
            '| Some\Other | Some\Listener\ThirdListener |'.PHP_EOL.
            '+------------+-----------------------------+'.PHP_EOL);
    }

    public function testWithEventSubscribe()
    {
        $this->testRoutine(
            [],
            [TestSubscriberServiceProvider::class],
            '+-------------------------------+---------------------------------------------------------------------+'.PHP_EOL.
            '| Event                         | Listeners                                                           |'.PHP_EOL.
            '+-------------------------------+---------------------------------------------------------------------+'.PHP_EOL.
            '| Illuminate\Auth\Events\Login  | Illuminate\Tests\Foundation\Console\TestSubscriber@handleUserLogin  |'.PHP_EOL.
            '| Illuminate\Auth\Events\Logout | Illuminate\Tests\Foundation\Console\TestSubscriber@handleUserLogout |'.PHP_EOL.
            '+-------------------------------+---------------------------------------------------------------------+'.PHP_EOL);
    }

    public function testWithClosure()
    {
        $this->testRoutine(
            [],
            [TestClosureServiceProvider::class],
            '+-------+-----------+'.PHP_EOL.
            '| Event | Listeners |'.PHP_EOL.
            '+-------+-----------+'.PHP_EOL.
            '| test  | Closure   |'.PHP_EOL.
            '+-------+-----------+'.PHP_EOL);
    }

    public function testWithWildCards()
    {
        $this->testRoutine(
            [],
            [TestWildCardServiceProvider::class],
            '+--------+-----------+'.PHP_EOL.
            '| Event  | Listeners |'.PHP_EOL.
            '+--------+-----------+'.PHP_EOL.
            '| test.* | Closure   |'.PHP_EOL.
            '+--------+-----------+'.PHP_EOL);
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
    }

    public function handleUserLogout($event)
    {
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
