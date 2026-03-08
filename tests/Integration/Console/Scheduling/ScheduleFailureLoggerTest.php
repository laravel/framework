<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\ScheduleFailureLogger;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class ScheduleFailureLoggerTest extends TestCase
{
    protected $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = storage_path('logs/schedule-failures.json');

        @unlink($this->logPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->logPath);

        parent::tearDown();
    }

    protected function makeEvent($command = 'artisan inspire')
    {
        $mutex = m::mock(EventMutex::class);
        $mutex->shouldReceive('create')->andReturn(true);
        $mutex->shouldReceive('exists')->andReturn(false);
        $mutex->shouldReceive('forget');

        return new Event($mutex, $command);
    }

    public function testLogsFailedTask()
    {
        $logger = new ScheduleFailureLogger(new Filesystem);

        $event = $this->makeEvent();
        $exception = new RuntimeException('Something went wrong');

        $logger->handleTaskFailed(new ScheduledTaskFailed($event, $exception));

        $this->assertFileExists($this->logPath);

        $lines = array_filter(explode("\n", file_get_contents($this->logPath)));
        $this->assertCount(1, $lines);

        $entry = json_decode($lines[0], true);
        $this->assertSame('failed', $entry['type']);
        $this->assertStringContainsString('Something went wrong', $entry['exception']);
        $this->assertArrayHasKey('timestamp', $entry);
        $this->assertArrayHasKey('mutex', $entry);
    }

    public function testLogsSkippedTask()
    {
        $logger = new ScheduleFailureLogger(new Filesystem);

        $event = $this->makeEvent();

        $logger->handleTaskSkipped(new ScheduledTaskSkipped($event));

        $this->assertFileExists($this->logPath);

        $lines = array_filter(explode("\n", file_get_contents($this->logPath)));
        $this->assertCount(1, $lines);

        $entry = json_decode($lines[0], true);
        $this->assertSame('skipped', $entry['type']);
        $this->assertArrayHasKey('timestamp', $entry);
    }

    public function testLogRotation()
    {
        $logger = new ScheduleFailureLogger(new Filesystem, 5);

        $event = $this->makeEvent();
        $exception = new RuntimeException('fail');

        for ($i = 0; $i < 7; $i++) {
            $logger->handleTaskFailed(new ScheduledTaskFailed($event, $exception));
        }

        $lines = array_filter(explode("\n", file_get_contents($this->logPath)));
        $this->assertCount(5, $lines);
    }
}
