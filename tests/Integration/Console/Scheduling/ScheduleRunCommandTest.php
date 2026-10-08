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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;
use ReflectionProperty;

use function Illuminate\Support\hours;
use function Illuminate\Support\minutes;

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

    public function test_missed_event_runs_once()
    {
        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->runIfMissed();

        $this->runScheduler('2026-03-24 03:00:00');
        $this->assertSame(1, $runs);

        // The scheduler was down at 03:00, so the missed run happens once it is back...
        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(2, $runs);

        $this->runScheduler('2026-03-25 09:01:00');
        $this->assertSame(2, $runs);
    }

    public function test_missed_event_respects_the_event_environments()
    {
        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->environments('production')->runIfMissed();

        $this->app['env'] = 'production';
        $this->runScheduler('2026-03-24 03:00:00');
        $this->assertSame(1, $runs);

        $this->app['env'] = 'local';
        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(1, $runs);
    }

    public function test_missed_event_runs_on_one_server_only()
    {
        $runs = 0;

        $task = $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->onOneServer()->runIfMissed();

        $this->runScheduler('2026-03-24 03:00:00');
        $this->assertSame(1, $runs);

        // Another server has already claimed the missed run for this minute...
        Carbon::setTestNow('2026-03-25 09:00:00');

        $this->app->make(CacheSchedulingMutex::class)->create($task, Carbon::now());

        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(1, $runs);
    }

    public function test_missed_events_do_not_run_without_run_if_missed()
    {
        $runs = 0;

        $task = $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00');

        $this->runScheduler('2026-03-24 03:00:00');
        $this->runScheduler('2026-03-25 09:00:00');

        $this->assertSame(1, $runs);
        $this->assertNull(Cache::get('illuminate:schedule:checked:'.$task->mutexName()));
    }

    public function test_missed_event_does_not_run_when_the_event_has_never_been_checked()
    {
        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->runIfMissed();

        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(0, $runs);

        $this->runScheduler('2026-03-26 03:00:00');
        $this->assertSame(1, $runs);
    }

    public function test_missed_event_does_not_run_twice_when_it_is_due()
    {
        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->runIfMissed();

        $this->runScheduler('2026-03-23 03:00:00');
        $this->runScheduler('2026-03-25 03:00:00');

        $this->assertSame(2, $runs);
    }

    public function test_missed_events_only_run_within_the_given_seconds()
    {
        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->runIfMissed(3600);

        $this->runScheduler('2026-03-24 03:00:00');
        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(1, $runs);

        $this->runScheduler('2026-03-26 03:30:00');
        $this->assertSame(2, $runs);
    }

    public function test_run_if_missed_accepts_an_interval()
    {
        $schedule = $this->app->make(Schedule::class);

        $this->assertSame(3600, $schedule->command('inspire')->runIfMissed(hours(1))->missedWithin);
        $this->assertSame(90, $schedule->command('inspire')->runIfMissed(minutes(1.5))->missedWithin);
        $this->assertSame(60, $schedule->command('inspire')->runIfMissed(60)->missedWithin);
    }

    public function test_missed_event_does_not_retry_runs_skipped_by_filters()
    {
        $runs = 0;
        $skip = false;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->skip(function () use (&$skip) {
            return $skip;
        })->runIfMissed();

        $this->runScheduler('2026-03-24 03:00:00');

        $skip = true;
        $this->runScheduler('2026-03-25 03:00:00');

        $skip = false;
        $this->runScheduler('2026-03-25 09:00:00');

        $this->assertSame(1, $runs);
    }

    public function test_missed_event_runs_after_maintenance_mode_ends()
    {
        Config::set('app.maintenance.driver', 'cache');
        Config::set('app.maintenance.store', 'array');

        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->runIfMissed();

        $this->runScheduler('2026-03-24 03:00:00');

        $this->artisan('down');
        $this->runScheduler('2026-03-25 03:00:00');
        $this->runScheduler('2026-03-25 04:00:00');
        $this->assertSame(1, $runs);

        $this->artisan('up');
        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(2, $runs);
    }

    public function test_missed_event_respects_the_event_timezone()
    {
        $runs = 0;

        $this->app->make(Schedule::class)->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->timezone('Asia/Dhaka')->runIfMissed();

        // 03:00 in Dhaka is 21:00 UTC on the previous day...
        $this->runScheduler('2026-03-23 21:00:00');
        $this->assertSame(1, $runs);

        $this->runScheduler('2026-03-24 20:00:00');
        $this->assertSame(1, $runs);

        $this->runScheduler('2026-03-25 09:00:00');
        $this->assertSame(2, $runs);
    }

    public function test_missed_event_uses_the_schedule_cache_store()
    {
        $this->app['config']->set('cache.stores.scheduler', ['driver' => 'array']);

        $runs = 0;

        $schedule = $this->app->make(Schedule::class)->useCache('scheduler');

        $schedule->call(function () use (&$runs) {
            $runs++;
        })->name('report')->dailyAt('03:00')->runIfMissed();

        $this->runScheduler('2026-03-24 03:00:00');

        Cache::store('scheduler')->flush();

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

        $this->app->forgetInstance(ScheduleRunCommand::class);
        $this->app[Kernel::class]->setArtisan(null);

        (new ReflectionProperty(Schedule::class, 'mutexCache'))
            ->setValue($this->app->make(Schedule::class), []);

        $this->artisan('schedule:run');
    }
}
