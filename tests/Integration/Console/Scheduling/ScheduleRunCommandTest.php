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
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::now());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schedule::$outputShouldBeForwardedToConsole = false;

        parent::tearDown();
    }

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

    /**
     * @throws BindingResolutionException
     */
    public function test_forward_output_to_console_preserves_failing_exit_code()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        $schedule = $this->app->make(Schedule::class);
        $task = $schedule->exec('exit 1')
            ->everyMinute()
            ->forwardOutputToConsole();

        $task->when(function () {
            return true;
        });

        $this->artisan('schedule:run');

        Event::assertDispatched(ScheduledTaskFailed::class, function ($event) use ($task) {
            return $event->task === $task;
        });
    }

    /**
     * @throws BindingResolutionException
     */
    public function test_forward_output_to_console_writes_output_to_configured_destination()
    {
        $path = tempnam(sys_get_temp_dir(), 'laravel-schedule-output-');

        $schedule = $this->app->make(Schedule::class);
        $command = PHP_OS_FAMILY === 'Windows' ? 'echo hello world' : "echo 'hello world'";
        $task = $schedule->exec($command)
            ->everyMinute()
            ->forwardOutputToConsole()
            ->sendOutputTo($path);

        $task->when(function () {
            return true;
        });

        $this->artisan('schedule:run');

        $this->assertSame('hello world', trim(file_get_contents($path)));

        unlink($path);
    }

    /**
     * @throws BindingResolutionException
     */
    public function test_forward_output_to_console_does_not_affect_background_events()
    {
        Event::fake([
            ScheduledTaskStarting::class,
            ScheduledTaskFinished::class,
            ScheduledTaskFailed::class,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'laravel-schedule-output-');
        file_put_contents($path, "existing\n");

        $schedule = $this->app->make(Schedule::class);
        $command = PHP_OS_FAMILY === 'Windows' ? 'echo hello world' : "echo 'hello world'";
        $task = $schedule->exec($command)
            ->everyMinute()
            ->forwardOutputToConsole()
            ->appendOutputTo($path)
            ->runInBackground();

        $task->when(function () {
            return true;
        });

        $this->artisan('schedule:run');

        // Give the detached background process a moment to finish writing its own output.
        usleep(300000);

        Event::assertNotDispatched(ScheduledTaskFailed::class);
        $this->assertStringContainsString('hello world', file_get_contents($path));

        unlink($path);
    }

    /**
     * @throws BindingResolutionException
     */
    public function test_schedule_wide_forward_output_to_console_applies_to_events_created_before_and_after()
    {
        $schedule = $this->app->make(Schedule::class);

        $beforePath = tempnam(sys_get_temp_dir(), 'laravel-schedule-output-');
        $afterPath = tempnam(sys_get_temp_dir(), 'laravel-schedule-output-');

        $before = $schedule->exec("echo 'before'")
            ->everyMinute()
            ->sendOutputTo($beforePath);

        $schedule->forwardOutputToConsole();

        $after = $schedule->exec("echo 'after'")
            ->everyMinute()
            ->sendOutputTo($afterPath);

        foreach ([$before, $after] as $task) {
            $task->when(function () {
                return true;
            });
        }

        $this->artisan('schedule:run');

        $this->assertSame('before', trim(file_get_contents($beforePath)));
        $this->assertSame('after', trim(file_get_contents($afterPath)));

        unlink($beforePath);
        unlink($afterPath);
    }

    public function test_repeat_events_does_not_mutate_started_at()
    {
        Carbon::setTestNow('2026-03-25 12:00:30');

        $command = new ScheduleRunCommand;
        $this->app->instance(ScheduleRunCommand::class, $command);

        $reflection = new ReflectionProperty($command, 'startedAt');
        $startedAt = $reflection->getValue($command);

        $originalTimestamp = $startedAt->timestamp;
        $originalMicro = $startedAt->micro;

        // Call repeatEvents with an empty collection so it exits immediately
        $reflection = new ReflectionMethod($command, 'repeatEvents');
        $command->setLaravel($this->app);

        // Set test time past the minute boundary so the while loop exits immediately
        Carbon::setTestNow('2026-03-25 12:01:01');
        $reflection->invoke($command, collect());

        // startedAt should not have been mutated to end of minute
        $startedAtAfter = (new ReflectionProperty($command, 'startedAt'))->getValue($command);
        $this->assertEquals($originalTimestamp, $startedAtAfter->timestamp);
        $this->assertEquals($originalMicro, $startedAtAfter->micro);
    }
}
