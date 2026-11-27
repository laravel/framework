<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

class WithScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2023-01-01');
        ScheduleListCommand::resolveTerminalWidthUsing(fn () => 80);
    }

    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withSchedule(function ($schedule) {
                $schedule->command('schedule:clear-cache')->everyMinute();
            })
            ->withCommands([__DIR__.'/Fixtures/console.php'])
            ->create();
    }

    public function testDisplaySchedule()
    {
        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('  0 * * * *  php artisan test:inspire .............. Next Due: 1 hour from now')
            ->expectsOutputToContain('  * * * * *  php artisan schedule:clear-cache .... Next Due: 1 minute from now');
    }

    public function testScheduleIsRegisteredOnceInEachClonedApplication()
    {
        $this->app->make(ConsoleKernel::class)->all();
        $expected = count($this->app->make(Schedule::class)->events());

        // Long-running servers such as Octane boot the application once and clone it for each request...
        $this->app->forgetInstance(ConsoleKernel::class);
        $this->app->forgetInstance(Schedule::class);

        foreach (range(1, 3) as $request) {
            $events = $this->inClonedApplication(function ($app) {
                $app->make(ConsoleKernel::class)->all();

                return count($app->make(Schedule::class)->events());
            });

            $this->assertSame($expected, $events, "Request #{$request} should see the schedule exactly once.");
        }
    }

    public function testResolvingTheConsoleKernelInAClonedApplicationDoesNotRetainItOnTheBaseApplication()
    {
        $this->app->forgetInstance(ConsoleKernel::class);

        $bootedCallbacks = new ReflectionProperty(Application::class, 'bootedCallbacks');
        $before = count($bootedCallbacks->getValue($this->app));

        foreach (range(1, 3) as $request) {
            $commands = $this->inClonedApplication(fn ($app) => $app->make(ConsoleKernel::class)->all());

            $this->assertArrayHasKey('test:inspire', $commands);
        }

        $this->assertCount($before, $bootedCallbacks->getValue($this->app));
    }

    /**
     * Run the callback against a clone of the application, the way Octane handles a request.
     */
    protected function inClonedApplication(Closure $callback)
    {
        $base = $this->app;

        $this->setCurrentApplication($sandbox = clone $base);

        try {
            return $callback($sandbox);
        } finally {
            $this->setCurrentApplication($base);
        }
    }

    protected function setCurrentApplication(Application $app)
    {
        $app->instance('app', $app);
        $app->instance(Container::class, $app);

        Container::setInstance($app);

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }
}
