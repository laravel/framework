<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\EventListCommand;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
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

    public function testWithNoEvent()
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $container = m::mock(Application::class);
        $container->shouldReceive('call');
        $container->shouldReceive('getProviders')
            ->with(EventServiceProvider::class)
            ->andReturn([]);

        $container->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(
                new OutputStyle($input, $output)
            );

        $command = new EventListCommand();
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();

        $this->assertEquals(self::DOES_NOT_HAVE_ANY_EVENTS, $output->fetch());
    }

    public function testWithMultipleEvents()
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $serviceProvider = m::mock(EventServiceProvider::class)->makePartial();
        $serviceProvider->shouldReceive('listens')
            ->andReturn([
                \Some\Event::class => [
                    \Some\Listener\FirstListener::class,
                    \Some\Listener\SecondListener::class,
                ],

                \Some\Other::class => [
                    \Some\Listener\ThirdListener::class,
                ],
            ]);

        $container = m::mock(Application::class);
        $container->shouldReceive('call');
        $container->shouldReceive('getProviders')
            ->with(EventServiceProvider::class)
            ->andReturn([$serviceProvider]);

        $container->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(new OutputStyle($input, $output));

        $command = new EventListCommand();
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();

        $this->assertEquals(
            '+------------+------------------------------+'.PHP_EOL.
            '| Event      | Listeners                    |'.PHP_EOL.
            '+------------+------------------------------+'.PHP_EOL.
            '| Some\Event | Some\Listener\FirstListener  |'.PHP_EOL.
            '|            | Some\Listener\SecondListener |'.PHP_EOL.
            '| Some\Other | Some\Listener\ThirdListener  |'.PHP_EOL.
            '+------------+------------------------------+'.PHP_EOL, $output->fetch()
        );
    }

    public function testWithFilteredMultipleEvents()
    {
        $input = new ArrayInput(['--event' => 'Other']);
        $output = new BufferedOutput();

        $serviceProvider = m::mock(EventServiceProvider::class)->makePartial();
        $serviceProvider->shouldReceive('listens')
            ->andReturn([
                \Some\Event::class => [
                    \Some\Listener\FirstListener::class,
                    \Some\Listener\SecondListener::class,
                ],

                \Some\Other::class => [
                    \Some\Listener\ThirdListener::class,
                ],
            ]);

        $container = m::mock(Application::class);
        $container->shouldReceive('call');
        $container->shouldReceive('getProviders')
            ->with(EventServiceProvider::class)
            ->andReturn([$serviceProvider]);

        $container->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(new OutputStyle($input, $output));

        $command = new EventListCommand();
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();

        $this->assertEquals(
            '+------------+-----------------------------+'.PHP_EOL.
            '| Event      | Listeners                   |'.PHP_EOL.
            '+------------+-----------------------------+'.PHP_EOL.
            '| Some\Other | Some\Listener\ThirdListener |'.PHP_EOL.
            '+------------+-----------------------------+'.PHP_EOL, $output->fetch()
        );
    }

    public function testWithEventSubscribe()
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $container = m::mock(Application::class);
        $container->shouldReceive('call');
        $container->shouldReceive('getProviders')
            ->with(EventServiceProvider::class)
            ->andReturn([new TestSubscriberServiceProvider($container)]);

        $container->shouldReceive('make')
            ->with(OutputStyle::class, m::any())
            ->andReturn(
                new OutputStyle($input, $output)
            );

        $container->shouldReceive('eventsAreCached')->andReturn(false);
        $container->shouldReceive('make')
            ->with(TestSubscriber::class)
            ->andReturn(new TestSubscriber());

        $command = new EventListCommand();
        $command->setLaravel($container);

        $command->run($input, $output);
        $command->handle();

        $this->assertEquals(
            '+-------------------------------+---------------------------------------------------------------------+'.PHP_EOL.
            '| Event                         | Listeners                                                           |'.PHP_EOL.
            '+-------------------------------+---------------------------------------------------------------------+'.PHP_EOL.
            '| Illuminate\Auth\Events\Login  | Illuminate\Tests\Foundation\Console\TestSubscriber@handleUserLogin  |'.PHP_EOL.
            '| Illuminate\Auth\Events\Logout | Illuminate\Tests\Foundation\Console\TestSubscriber@handleUserLogout |'.PHP_EOL.
            '+-------------------------------+---------------------------------------------------------------------+'.PHP_EOL
            , $output->fetch()
        );
    }
}

class TestSubscriberServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        TestSubscriber::class,
    ];
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
