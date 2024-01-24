<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Sleep;
use Orchestra\Testbench\TestCase;

class SubMinuteSchedulingTest extends TestCase
{
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schedule = $this->app->make(Schedule::class);
    }

    public function test_it_doesnt_wait_for_sub_minute_events_when_nothing_is_scheduled()
    {
        Carbon::setTestNow(now()->startOfMinute());
        Sleep::fake();

        $this->artisan('schedule:run')
            ->expectsOutputToContain('No scheduled commands are ready to run.');

        Sleep::assertNeverSlept();
    }

    public function test_it_doesnt_wait_for_sub_minute_events_when_none_are_scheduled()
    {
        $this->schedule
            ->call(fn () => true)
            ->everyMinute();

        Carbon::setTestNow(now()->startOfMinute());
        Sleep::fake();

        $this->artisan('schedule:run')
            ->expectsOutputToContain('Running [Callback]');

        Sleep::assertNeverSlept();
    }

    /** @dataProvider frequencyProvider */
    public function test_it_runs_sub_minute_callbacks($frequency, $expectedRuns)
    {
        $runs = 0;
        $this->schedule->call(function () use (&$runs) {
            $runs++;
        })->{$frequency}();

        Carbon::setTestNow(now()->startOfMinute());
        Sleep::fake();
        Sleep::whenFakingSleep(fn ($duration) => Carbon::setTestNow(now()->add($duration)));

        $this->artisan('schedule:run')
            ->expectsOutputToContain('Running [Callback]');

        Sleep::assertSleptTimes(600);
        $this->assertEquals($expectedRuns, $runs);
    }

    public function test_it_runs_multiple_sub_minute_callbacks()
    {
        $everySecondRuns = 0;
        $this->schedule->call(function () use (&$everySecondRuns) {
            $everySecondRuns++;
        })->everySecond();

        $everyThirtySecondsRuns = 0;
        $this->schedule->call(function () use (&$everyThirtySecondsRuns) {
            $everyThirtySecondsRuns++;
        })->everyThirtySeconds();

        Carbon::setTestNow(now()->startOfMinute());
        Sleep::fake();
        Sleep::whenFakingSleep(fn ($duration) => Carbon::setTestNow(now()->add($duration)));

        $this->artisan('schedule:run')
            ->expectsOutputToContain('Running [Callback]');

        Sleep::assertSleptTimes(600);
        $this->assertEquals(60, $everySecondRuns);
        $this->assertEquals(2, $everyThirtySecondsRuns);
    }

    public function test_sub_minute_scheduling_can_be_interrupted()
    {
        $runs = 0;
        $this->schedule->call(function () use (&$runs) {
            $runs++;
        })->everySecond();

        Carbon::setTestNow(now()->startOfMinute());
        $startedAt = now();
        Sleep::fake();
        Sleep::whenFakingSleep(function ($duration) use ($startedAt) {
            Carbon::setTestNow(now()->add($duration));

            if ($startedAt->diffInSeconds() >= 30) {
                $this->artisan('schedule:interrupt')
                    ->expectsOutputToContain('Broadcasting schedule interrupt signal.');
            }
        });

        $this->artisan('schedule:run')
            ->expectsOutputToContain('Running [Callback]');

        Sleep::assertSleptTimes(300);
        $this->assertEquals(30, $runs);
        $this->assertEquals(30, $startedAt->diffInSeconds(now()));
    }

    public function test_sub_minute_events_stop_for_the_rest_of_the_minute_once_maintenance_mode_is_enabled()
    {
        $runs = 0;
        $this->schedule->call(function () use (&$runs) {
            $runs++;
        })->everySecond();

        Config::set('app.maintenance.driver', 'cache');
        Config::set('app.maintenance.store', 'array');
        Carbon::setTestNow(now()->startOfMinute());
        $startedAt = now();
        Sleep::fake();
        Sleep::whenFakingSleep(function ($duration) use ($startedAt) {
            Carbon::setTestNow(now()->add($duration));

            if ($startedAt->diffInSeconds() >= 30 && ! $this->app->isDownForMaintenance()) {
                $this->artisan('down');
            }

            if ($startedAt->diffInSeconds() >= 40 && $this->app->isDownForMaintenance()) {
                $this->artisan('up');
            }
        });

        $this->artisan('schedule:run')
            ->expectsOutputToContain('Running [Callback]');

        Sleep::assertSleptTimes(600);
        $this->assertEquals(30, $runs);
    }

    public function test_sub_minute_events_can_be_run_in_maintenance_mode()
    {
        $runs = 0;
        $this->schedule->call(function () use (&$runs) {
            $runs++;
        })->everySecond()->evenInMaintenanceMode();

        Config::set('app.maintenance.driver', 'cache');
        Config::set('app.maintenance.store', 'array');
        Carbon::setTestNow(now()->startOfMinute());
        $startedAt = now();
        Sleep::fake();
        Sleep::whenFakingSleep(function ($duration) use ($startedAt) {
            Carbon::setTestNow(now()->add($duration));

            if (now()->diffInSeconds($startedAt) >= 30 && ! $this->app->isDownForMaintenance()) {
                $this->artisan('down');
            }
        });

        $this->artisan('schedule:run')
            ->expectsOutputToContain('Running [Callback]');

        Sleep::assertSleptTimes(600);
        $this->assertEquals(60, $runs);
    }

    public function test_sub_minute_scheduling_respects_filters()
    {
        $runs = 0;
        $this->schedule->call(function () use (&$runs) {
            $runs++;
        })->everySecond()->when(fn () => now()->second % 2 === 0);

        Carbon::setTestNow(now()->startOfMinute());
        Sleep::fake();
        Sleep::whenFakingSleep(fn ($duration) => Carbon::setTestNow(now()->add($duration)));

        $this->artisan('schedule:run')
            ->expectsOutputToContain('Running [Callback]');

        Sleep::assertSleptTimes(600);
        $this->assertEquals(30, $runs);
    }

    public function test_sub_minute_scheduling_can_run_on_one_server()
    {
        $runs = 0;
        $this->schedule->call(function () use (&$runs) {
            $runs++;
        })->everySecond()->name('test')->onOneServer();

        $startedAt = now()->startOfMinute();
        Carbon::setTestNow($startedAt);
        Sleep::fake();
        Sleep::whenFakingSleep(fn ($duration) => Carbon::setTestNow(now()->add($duration)));

        $this->app->instance(Schedule::class, clone $this->schedule);
        $this->artisan('schedule:run')
            ->expectsOutputToContain('Running [test]');

        Sleep::assertSleptTimes(600);
        $this->assertEquals(60, $runs);

        // Fake a second server running at the same minute.
        Carbon::setTestNow($startedAt);

        $this->app->instance(Schedule::class, clone $this->schedule);
        $this->artisan('schedule:run')
            ->expectsOutputToContain('Skipping [test]');

        Sleep::assertSleptTimes(1200);
        $this->assertEquals(60, $runs);
    }

    public static function frequencyProvider()
    {
        return [
            'everySecond' => ['everySecond', 60],
            'everyTwoSeconds' => ['everyTwoSeconds', 30],
            'everyFiveSeconds' => ['everyFiveSeconds', 12],
            'everyTenSeconds' => ['everyTenSeconds', 6],
            'everyFifteenSeconds' => ['everyFifteenSeconds', 4],
            'everyTwentySeconds' => ['everyTwentySeconds', 3],
            'everyThirtySeconds' => ['everyThirtySeconds', 2],
        ];
    }
}
