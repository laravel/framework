<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
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
    protected function tearDown(): void
    {
        m::close();
    }

    protected function routine(array $inputParams, array $providerClasses, string $expectedOutput)
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
        $actualLines = array_filter(preg_split("/\r\n|\n|\r/", $actualOutput));
        $expectedLines = array_filter(preg_split("/\r\n|\n|\r/", $expectedOutput));

        $this->assertEquals($expectedLines, $actualLines);
    }

    public function testWithNoEvent()
    {
        $this->routine(
            [],
            [EventServiceProvider::class],
            "Your application doesn't have any events matching the given criteria.");
    }

    public function testWithMultipleEvents()
    {
        $this->routine(
            [],
            [TestMultipleEventsServiceProvider::class, EventServiceProvider::class],
            <<<OUTPUT
+-------------------------------------+---------------------------------------------------------+
| Event                               | Listeners                                               |
+-------------------------------------+---------------------------------------------------------+
| Illuminate\Cache\Events\CacheHit    | Illuminate\Tests\Foundation\Console\CacheHitListener    |
| Illuminate\Cache\Events\CacheMissed | Illuminate\Tests\Foundation\Console\CacheMissedListener |
+-------------------------------------+---------------------------------------------------------+
OUTPUT
        );
    }

    public function testWithFilteredMultipleEvents()
    {
        $this->routine(
            ['--event' => 'Missed'],
            [TestMultipleEventsServiceProvider::class, TestClosureServiceProvider::class],
            <<<OUTPUT
+-------------------------------------+---------------------------------------------------------+
| Event                               | Listeners                                               |
+-------------------------------------+---------------------------------------------------------+
| Illuminate\Cache\Events\CacheMissed | Illuminate\Tests\Foundation\Console\CacheMissedListener |
+-------------------------------------+---------------------------------------------------------+
OUTPUT
        );
    }

    public function testWithEventSubscribe()
    {
        $this->routine(
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
        $this->routine(
            [],
            [TestClosureServiceProvider::class],
            <<<'OUTPUT'
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
        $this->routine(
            [],
            [TestWildCardServiceProvider::class],
            <<<'OUTPUT'
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
        CacheHit::class => [
            CacheHitListener::class,
        ],

        CacheMissed::class => [
            CacheMissedListener::class,
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

class CacheHitListener
{
    public function handle(CacheHit $event)
    {
    }
}

class CacheMissedListener
{
    public function handle(CacheMissed $event)
    {
    }
}
