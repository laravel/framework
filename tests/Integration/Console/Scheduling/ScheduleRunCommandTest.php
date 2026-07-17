<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class ScheduleRunCommandTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test_failing_command_in_foreground_triggers_event()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        // Create a schedule and add the command
        $schedule = $this->app->make(Schedule::class);
        $task = $schedule->exec('exit 1')
            ->everyMinute();

        // Make sure it will run regardless of schedule
        $task->when(function () {
            return true;
        });

        // Execute the scheduler
        $this->artisan('schedule:run');

        // Verify the event sequence
        Event::assertDispatched(ScheduledTaskStarting::class);
        Event::assertDispatched(ScheduledTaskFinished::class);
        Event::assertDispatched(ScheduledTaskFailed::class, function ($event) use ($task) {
            return $event->task === $task &&
                   $event->exception->getMessage() === 'Scheduled command [exit 1] failed with exit code [1].';
        });
    }

    /**
     * @throws BindingResolutionException
     */
    public function test_failing_command_in_background_does_not_trigger_event()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        // Create a schedule and add the command
        $schedule = $this->app->make(Schedule::class);
        $task = $schedule->exec('exit 1')
            ->everyMinute()
            ->runInBackground();

        // Make sure it will run regardless of schedule
        $task->when(function () {
            return true;
        });

        // Execute the scheduler
        $this->artisan('schedule:run');

        // Verify the event sequence
        Event::assertDispatched(ScheduledTaskStarting::class);
        Event::assertDispatched(ScheduledTaskFinished::class);
        Event::assertNotDispatched(ScheduledTaskFailed::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function test_successful_command_does_not_trigger_event()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        // Create a schedule and add the command
        $schedule = $this->app->make(Schedule::class);
        $task = $schedule->exec('exit 0')
            ->everyMinute();

        // Make sure it will run regardless of schedule
        $task->when(function () {
            return true;
        });

        // Execute the scheduler
        $this->artisan('schedule:run');

        // Verify the event sequence
        Event::assertDispatched(ScheduledTaskStarting::class);
        Event::assertDispatched(ScheduledTaskFinished::class);
        Event::assertNotDispatched(ScheduledTaskFailed::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function test_command_with_no_explicit_return_does_not_trigger_event()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        // Create a schedule and add the command that just performs an action without explicit exit
        $schedule = $this->app->make(Schedule::class);
        $command = PHP_OS_FAMILY === 'Windows' ? 'cmd /c exit 0' : 'true';
        $task = $schedule->exec($command)
            ->everyMinute();

        // Make sure it will run regardless of schedule
        $task->when(function () {
            return true;
        });

        // Execute the scheduler
        $this->artisan('schedule:run');

        // Verify the event sequence
        Event::assertDispatched(ScheduledTaskStarting::class);
        Event::assertDispatched(ScheduledTaskFinished::class);
        Event::assertNotDispatched(ScheduledTaskFailed::class);
    }

    /**
     * @throws BindingResolutionException
     */
    public function test_successful_command_in_background_does_not_trigger_event()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        // Create a schedule and add the command
        $schedule = $this->app->make(Schedule::class);
        $task = $schedule->exec('exit 0')
            ->everyMinute()
            ->runInBackground();

        // Make sure it will run regardless of schedule
        $task->when(function () {
            return true;
        });

        // Execute the scheduler
        $this->artisan('schedule:run');

        // Verify the event sequence
        Event::assertDispatched(ScheduledTaskStarting::class);
        Event::assertDispatched(ScheduledTaskFinished::class);
        Event::assertNotDispatched(ScheduledTaskFailed::class);
    }

    public function test_overlapping_task_finished_event_indicates_skipped()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        $this->app->instance(EventMutex::class, new class implements EventMutex
        {
            public function create(\Illuminate\Console\Scheduling\Event $event)
            {
                return false;
            }

            public function exists(\Illuminate\Console\Scheduling\Event $event)
            {
                return false;
            }

            public function forget(\Illuminate\Console\Scheduling\Event $event)
            {
                //
            }
        });

        $ran = false;
        $schedule = $this->app->make(Schedule::class);
        $task = $schedule->call(function () use (&$ran) {
            $ran = true;
        })->name('test')->withoutOverlapping()->everyMinute();

        $this->artisan('schedule:run');

        Event::assertDispatched(ScheduledTaskStarting::class, function ($event) use ($task) {
            return $event->task === $task;
        });
        Event::assertDispatched(ScheduledTaskFinished::class, function ($event) use ($task) {
            return $event->task === $task &&
                   $event->task->skippedBecauseOverlapping === true;
        });
        Event::assertNotDispatched(ScheduledTaskFailed::class);
        $this->assertFalse($ran);
    }

    /**
     * @throws BindingResolutionException
     */
    public function test_command_with_no_explicit_return_in_background_does_not_trigger_event()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        // Create a schedule and add the command that just performs an action without explicit exit
        $schedule = $this->app->make(Schedule::class);
        $task = $schedule->exec('true')
            ->everyMinute()
            ->runInBackground();

        // Make sure it will run regardless of schedule
        $task->when(function () {
            return true;
        });

        // Execute the scheduler
        $this->artisan('schedule:run');

        // Verify the event sequence
        Event::assertDispatched(ScheduledTaskStarting::class);
        Event::assertDispatched(ScheduledTaskFinished::class);
        Event::assertNotDispatched(ScheduledTaskFailed::class);
    }

    public function test_repeat_events_does_not_mutate_started_at()
    {
        Carbon::setTestNow('2026-03-25 12:00:30');

        $command = new ScheduleRunCommand;
        $this->app->instance(ScheduleRunCommand::class, $command);

        $reflection = new ReflectionProperty($command, 'startedAt');
        $startedAt = $reflection->getValue($command);

        $originalTimestamp = $startedAt->getTimestamp();
        $originalMicro = $startedAt->micro;

        // Call repeatEvents with an empty collection so it exits immediately
        $reflection = new ReflectionMethod($command, 'repeatEvents');
        $command->setLaravel($this->app);

        // Set test time past the minute boundary so the while loop exits immediately
        Carbon::setTestNow('2026-03-25 12:01:01');
        $reflection->invoke($command, collect());

        // startedAt should not have been mutated to end of minute
        $startedAtAfter = (new ReflectionProperty($command, 'startedAt'))->getValue($command);
        $this->assertSame($originalTimestamp, $startedAtAfter->getTimestamp());
        $this->assertSame($originalMicro, $startedAtAfter->micro);
    }
}
