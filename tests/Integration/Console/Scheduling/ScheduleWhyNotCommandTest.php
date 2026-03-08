<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleWhyNotCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;

class ScheduleWhyNotCommandTest extends TestCase
{
    protected $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2023-01-01 00:00:00');

        $this->schedule = $this->app->make(Schedule::class);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('logs/schedule-failures.json'));

        parent::tearDown();
    }

    public function testDisplaysEmptySchedule()
    {
        $this->withoutMockingConsoleOutput();
        $this->artisan(ScheduleWhyNotCommand::class);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString('No scheduled tasks have been defined.', $output);
    }

    public function testDisplaysScheduleWithNoFailures()
    {
        $this->schedule->command('inspire')->everyMinute();

        $this->withoutMockingConsoleOutput();
        $this->artisan(ScheduleWhyNotCommand::class);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString('inspire', $output);
        $this->assertStringContainsString('OK', $output);
    }

    public function testDisplaysFailureFromLog()
    {
        $this->schedule->command('inspire')->everyMinute();

        $entry = json_encode([
            'timestamp' => '2023-01-01T00:00:00+00:00',
            'command' => 'php artisan inspire',
            'description' => '',
            'type' => 'failed',
            'exit_code' => 1,
            'exception' => 'RuntimeException: Something broke',
            'mutex' => 'framework/schedule-'.sha1('* * * * *'.'php artisan inspire'),
        ]);

        $files = new Filesystem;
        $files->ensureDirectoryExists(storage_path('logs'));
        $files->put(storage_path('logs/schedule-failures.json'), $entry."\n");

        $this->withoutMockingConsoleOutput();
        $this->artisan(ScheduleWhyNotCommand::class);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString('FAILED', $output);
        $this->assertStringContainsString('RuntimeException: Something broke', $output);
    }

    public function testDisplaysSkippedFromLog()
    {
        $this->schedule->command('inspire')->everyMinute();

        $entry = json_encode([
            'timestamp' => '2023-01-01T00:00:00+00:00',
            'command' => 'php artisan inspire',
            'description' => '',
            'type' => 'skipped',
            'mutex' => 'framework/schedule-'.sha1('* * * * *'.'php artisan inspire'),
        ]);

        $files = new Filesystem;
        $files->ensureDirectoryExists(storage_path('logs'));
        $files->put(storage_path('logs/schedule-failures.json'), $entry."\n");

        $this->withoutMockingConsoleOutput();
        $this->artisan(ScheduleWhyNotCommand::class);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString('SKIPPED', $output);
    }

    public function testJsonOutput()
    {
        $this->schedule->command('inspire')->everyMinute();

        $this->withoutMockingConsoleOutput();
        $this->artisan(ScheduleWhyNotCommand::class, ['--json' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertJson($output);
        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('command', $data[0]);
        $this->assertArrayHasKey('status', $data[0]);
        $this->assertArrayHasKey('diagnostics', $data[0]);
    }

    public function testEventFilter()
    {
        $this->schedule->command('inspire')->everyMinute();
        $this->schedule->command('migrate')->daily();

        $this->withoutMockingConsoleOutput();
        $this->artisan(ScheduleWhyNotCommand::class, ['--event' => 'inspire']);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString('inspire', $output);
        $this->assertStringNotContainsString('migrate', $output);
    }

    public function testLimitOption()
    {
        $this->schedule->command('inspire')->everyMinute();

        $files = new Filesystem;
        $files->ensureDirectoryExists(storage_path('logs'));

        $entries = '';
        for ($i = 1; $i <= 3; $i++) {
            $entries .= json_encode([
                'timestamp' => '2023-01-01T00:00:0'.$i.'+00:00',
                'command' => 'php artisan inspire',
                'description' => '',
                'type' => 'failed',
                'exit_code' => 1,
                'exception' => 'RuntimeException: Failure '.$i,
                'mutex' => 'framework/schedule-'.sha1('* * * * *'.'php artisan inspire'),
            ])."\n";
        }
        $files->put(storage_path('logs/schedule-failures.json'), $entries);

        $this->withoutMockingConsoleOutput();
        $this->artisan(ScheduleWhyNotCommand::class, ['--json' => true, '--limit' => 2]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertJson($output);
        $data = json_decode($output, true);
        $this->assertCount(1, $data);
        $this->assertSame('FAILED', $data[0]['status']);
        // With --limit=2, only the last 2 entries are taken, so the last failure message should be "Failure 3"
        $this->assertStringContainsString('Failure 3', $data[0]['last_failure']);
    }
}
