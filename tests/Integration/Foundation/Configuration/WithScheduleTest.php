<?php

namespace Illuminate\Tests\Integration\Foundation\Configuration;

use Illuminate\Console\Scheduling\ScheduleListCommand;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;

class WithScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2023-01-01');
        ScheduleListCommand::resolveTerminalWidthUsing(fn () => 80);
    }

    protected function tearDown(): void
    {
        ScheduleListCommand::resolveTerminalWidthUsing(null);

        parent::tearDown();
    }

    protected function resolveApplication()
    {
        return Application::configure(static::applicationBasePath())
            ->withSchedule(function ($schedule) {
                $schedule->command('schedule:clear-cache')->everyMinute();
            })->create();
    }

    public function testDisplaySchedule()
    {
        $this->artisan(ScheduleListCommand::class)
            ->assertSuccessful()
            ->expectsOutputToContain('  * * * * *  php artisan schedule:clear-cache');
    }
}
