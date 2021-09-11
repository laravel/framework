<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBuildCommandUsingUnix()
    {
        if (windows_os()) {
            $this->markTestSkipped('Skipping since operating system is Windows');
        }

        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $this->assertSame("php -i > '/dev/null' 2>&1", $event->buildCommand());
    }

    public function testBuildCommandUsingWindows()
    {
        if (! windows_os()) {
            $this->markTestSkipped('Skipping since operating system is not Windows');
        }

        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $this->assertSame('php -i > "NUL" 2>&1', $event->buildCommand());
    }

    public function testBuildCommandInBackgroundUsingUnix()
    {
        if (windows_os()) {
            $this->markTestSkipped('Skipping since operating system is Windows');
        }

        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->runInBackground();

        $scheduleId = '"framework'.DIRECTORY_SEPARATOR.'schedule-eeb46c93d45e928d62aaf684d727e213b7094822"';

        $this->assertSame("(php -i > '/dev/null' 2>&1 ; '".PHP_BINARY."' artisan schedule:finish {$scheduleId} \"$?\") > '/dev/null' 2>&1 &", $event->buildCommand());
    }

    public function testBuildCommandInBackgroundUsingWindows()
    {
        if (! windows_os()) {
            $this->markTestSkipped('Skipping since operating system is not Windows');
        }

        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->runInBackground();

        $scheduleId = '"framework'.DIRECTORY_SEPARATOR.'schedule-eeb46c93d45e928d62aaf684d727e213b7094822"';

        $this->assertSame('start /b cmd /v:on /c "(php -i & "'.PHP_BINARY.'" artisan schedule:finish '.$scheduleId.' ^!ERRORLEVEL^!) > "NUL" 2>&1"', $event->buildCommand());
    }

    public function testBuildCommandSendOutputTo()
    {
        $quote = (DIRECTORY_SEPARATOR === '\\') ? '"' : "'";

        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $event->sendOutputTo('/dev/null');
        $this->assertSame("php -i > {$quote}/dev/null{$quote} 2>&1", $event->buildCommand());

        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $event->sendOutputTo('/my folder/foo.log');
        $this->assertSame("php -i > {$quote}/my folder/foo.log{$quote} 2>&1", $event->buildCommand());
    }

    public function testBuildCommandAppendOutput()
    {
        $quote = (DIRECTORY_SEPARATOR === '\\') ? '"' : "'";

        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $event->appendOutputTo('/dev/null');
        $this->assertSame("php -i >> {$quote}/dev/null{$quote} 2>&1", $event->buildCommand());
    }

    public function testNextRunDate()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->dailyAt('10:15');

        $this->assertSame('10:15:00', $event->nextRunDate()->toTimeString());
    }
}
