<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Events\Dispatcher;
use Orchestra\Testbench\TestCase;

class ScheduleRunCommandTest extends TestCase
{
    /**
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    public function test_scheduled_failed_event_is_dispatch_on_schedule_failure()
    {
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:failing';

                protected $description = 'A test command that always fails';

                public function handle()
                {
                    throw new Exception('Test command failed as expected');
                }
            }
        );

        $schedule = $this->app->make(Schedule::class);
        $task = $schedule->command('test:failing');

        $reflection = new \ReflectionClass($task);
        $property = $reflection->getProperty('expression');
        $property->setValue($task, '* * * * *');

        $failureDetected = false;
        $this->app->make(Dispatcher::class)->listen(
            ScheduledTaskFailed::class,
            function (ScheduledTaskFailed $event) use (&$failureDetected, $task) {
                if ($event->task === $task) {
                    $failureDetected = true;
                }
            }
        );

        $this->artisan('schedule:run');

        $this->assertTrue($failureDetected, 'ScheduledTaskFailed event was not dispatched');
    }
}
