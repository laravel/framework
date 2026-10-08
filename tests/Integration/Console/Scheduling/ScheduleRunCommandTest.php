<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Contracts\Console\Kernel;
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
        $this->assertEquals($originalTimestamp, $startedAtAfter->getTimestamp());
        $this->assertEquals($originalMicro, $startedAtAfter->micro);
    }

    public function test_catch_up_runs_a_missed_event_once()
    {
        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->catchUp();

        $this->runScheduler('2026-03-24 03:00:00');
        $this->assertSame(1, $runs);

        // The scheduler was down at 03:00, so the missed run happens once it is back...
        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(2, $runs);

        $this->runScheduler('2026-03-25 09:01:00');
        $this->assertSame(2, $runs);
    }

    public function test_catch_up_respects_the_event_environments()
    {
        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->environments('production')->catchUp();

        $this->app['env'] = 'production';
        $this->runScheduler('2026-03-24 03:00:00');
        $this->assertSame(1, $runs);

        $this->app['env'] = 'local';
        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(1, $runs);
    }

    public function test_catch_up_runs_on_one_server_only()
    {
        $runs = 0;

        $task = $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->onOneServer()->catchUp();

        $this->runScheduler('2026-03-24 03:00:00');
        $this->assertSame(1, $runs);

        // Another server has already claimed the missed run for this minute...
        $this->app->make(CacheSchedulingMutex::class)->create($task, Carbon::parse('2026-03-25 09:00:00'));

        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(1, $runs);
    }

    /**
     * Run the scheduler at the given time, as a fresh "schedule:run" process would.
     *
     * @param  string  $time
     * @return void
     */
    protected function runScheduler($time)
    {
        Carbon::setTestNow($time);

        $this->app[Kernel::class]->setArtisan(null);

        (new ReflectionProperty(Schedule::class, 'mutexCache'))
            ->setValue($this->app->make(Schedule::class), []);

        $this->artisan('schedule:run');
    }
}
