<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Events\Dispatcher;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class CallbackSchedulingTest extends TestCase
{
    protected $log = [];

    protected function setUp(): void
    {
        parent::setUp();

        $cache = new class implements Factory
        {
            public $store;

            public function __construct()
            {
                $this->store = new Repository(new ArrayStore(true));
            }

            public function store($name = null)
            {
                return $this->store;
            }
        };

        $container = Container::getInstance();

        $container->instance(EventMutex::class, new CacheEventMutex($cache));
        $container->instance(SchedulingMutex::class, new CacheSchedulingMutex($cache));
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    /**
     * @dataProvider executionProvider
     */
    public function testExecutionOrder($background)
    {
        $event = $this->app->make(Schedule::class)
            ->call($this->logger('call'))
            ->after($this->logger('after 1'))
            ->before($this->logger('before 1'))
            ->after($this->logger('after 2'))
            ->before($this->logger('before 2'));

        if ($background) {
            $event->runInBackground();
        }

        $this->artisan('schedule:run');

        $this->assertLogged('before 1', 'before 2', 'call', 'after 1', 'after 2');
    }

    public function testExceptionHandlingInCallback()
    {
        $event = $this->app->make(Schedule::class)
            ->call($this->logger('call'))
            ->name('test-event')
            ->withoutOverlapping();

        // Set up "before" and "after" hooks to ensure they're called
        $event->before($this->logger('before'))->after($this->logger('after'));

        // Register a hook to validate that the mutex was initially created
        $mutexWasCreated = false;
        $event->before(function () use (&$mutexWasCreated, $event) {
            $mutexWasCreated = $event->mutex->exists($event);
        });

        // We'll trigger an exception in an "after" hook to test exception handling
        $event->after(function () {
            throw new RuntimeException;
        });

        // Because exceptions are caught by the ScheduleRunCommand, we need to listen for
        // the "failed" event to check whether our exception was actually thrown
        $failed = false;
        $this->app->make(Dispatcher::class)
            ->listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $failure) use (&$failed, $event) {
                if ($failure->task === $event) {
                    $failed = true;
                }
            });

        $this->artisan('schedule:run');

        // Hooks and execution should happn in correct order
        $this->assertLogged('before', 'call', 'after');

        // Our exception should have resulted in a failure event
        $this->assertTrue($failed);

        // Validate that the mutex was originally created, but that it's since
        // been removed (even though an exception was thrown)
        $this->assertTrue($mutexWasCreated);
        $this->assertFalse($event->mutex->exists($event));
    }

    public function executionProvider()
    {
        return [
            'Foreground' => [false],
            'Background' => [true],
        ];
    }

    protected function logger($message)
    {
        return function () use ($message) {
            $this->log[] = $message;
        };
    }

    protected function assertLogged(...$message)
    {
        $this->assertEquals($message, $this->log);
    }
}
