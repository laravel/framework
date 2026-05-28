<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Mockery as m;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;

use function Illuminate\Support\php_binary;

class EventTest extends TestCase
{
    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testBuildCommandUsingUnix()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $this->assertSame("php -i > '/dev/null' 2>&1", $event->buildCommand());
    }

    #[RequiresOperatingSystem('Windows')]
    public function testBuildCommandUsingWindows()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $this->assertSame('php -i > "NUL" 2>&1', $event->buildCommand());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testBuildCommandInBackgroundUsingUnix()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->runInBackground();

        $scheduleId = '"framework'.DIRECTORY_SEPARATOR.'schedule-eeb46c93d45e928d62aaf684d727e213b7094822"';

        $this->assertSame("(php -i > '/dev/null' 2>&1 ; '".php_binary()."' 'artisan' schedule:finish {$scheduleId} \"$?\") > '/dev/null' 2>&1 &", $event->buildCommand());
    }

    #[RequiresOperatingSystem('Windows')]
    public function testBuildCommandInBackgroundUsingWindows()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->runInBackground();

        $scheduleId = '"framework'.DIRECTORY_SEPARATOR.'schedule-eeb46c93d45e928d62aaf684d727e213b7094822"';

        $this->assertSame('start /b cmd /v:on /c "(php -i & '.php_binary().' artisan schedule:finish '.$scheduleId.' ^!ERRORLEVEL^!) > "NUL" 2>&1"', $event->buildCommand());
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

    public function testCustomMutexName()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->description('Fancy command description');

        $this->assertSame('framework'.DIRECTORY_SEPARATOR.'schedule-eeb46c93d45e928d62aaf684d727e213b7094822', $event->mutexName());

        $event->createMutexNameUsing(function (Event $event) {
            return Str::slug($event->description);
        });

        $this->assertSame('fancy-command-description', $event->mutexName());
    }

    public function testBeforeAndAfterCallbacksCanReceiveEvent()
    {
        $container = new Container;
        $beforeEvent = null;
        $afterEvent = null;
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $event->before(function (Event $event) use (&$beforeEvent) {
            $beforeEvent = $event;
        });

        $event->after(function (Event $event) use (&$afterEvent) {
            $afterEvent = $event;
        });

        $event->callBeforeCallbacks($container);
        $event->callAfterCallbacks($container);

        $this->assertSame($event, $beforeEvent);
        $this->assertSame($event, $afterEvent);
    }

    public function testFilterCallbacksCanReceiveEvent()
    {
        $container = new Container;
        $filterEvent = null;
        $rejectEvent = null;
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $event->when(function (Event $event) use (&$filterEvent) {
            $filterEvent = $event;

            return true;
        });

        $event->skip(function (Event $event) use (&$rejectEvent) {
            $rejectEvent = $event;

            return false;
        });

        $this->assertTrue($event->filtersPass($container));
        $this->assertSame($event, $filterEvent);
        $this->assertSame($event, $rejectEvent);
    }

    public function testEventCallbackResolvesByTypeRegardlessOfParameterName()
    {
        $container = new Container;
        $beforeEvent = null;
        $filterEvent = null;
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $event->before(function (Event $scheduledEvent) use (&$beforeEvent) {
            $beforeEvent = $scheduledEvent;
        });

        $event->when(function (Event $scheduledEvent) use (&$filterEvent) {
            $filterEvent = $scheduledEvent;

            return true;
        });

        $event->callBeforeCallbacks($container);
        $this->assertTrue($event->filtersPass($container));

        $this->assertSame($event, $beforeEvent);
        $this->assertSame($event, $filterEvent);
    }

    public function testEventCallbackDoesNotInjectIntoUnrelatedTypedParameters()
    {
        $container = new Container;
        $stringValue = null;
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $container->instance(Stringable::class, Str::of('injected-string'));

        $event->before(function (Stringable $value) use (&$stringValue) {
            $stringValue = (string) $value;
        });

        $event->callBeforeCallbacks($container);

        $this->assertSame('injected-string', $stringValue);
    }

    public function testFilterCallbacksMayBeInvokableObjects()
    {
        $container = new Container;
        $filter = new class
        {
            public int $calls = 0;

            public function __invoke(): bool
            {
                $this->calls++;

                return true;
            }
        };
        $reject = new class
        {
            public int $calls = 0;

            public function __invoke(): bool
            {
                $this->calls++;

                return false;
            }
        };
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $event->when($filter);
        $event->skip($reject);

        $this->assertTrue($event->filtersPass($container));
        $this->assertSame(1, $filter->calls);
        $this->assertSame(1, $reject->calls);
    }

    public function testRunIndicatesWhenSkippedBecauseOverlapping()
    {
        $container = new Container;
        $beforeCallbackCalled = false;
        $mutex = m::mock(EventMutex::class);
        $event = new class($mutex, 'php -i') extends Event
        {
            public $executed = false;

            protected function execute($container)
            {
                $this->executed = true;

                return 0;
            }
        };

        $event->withoutOverlapping();
        $event->before(function () use (&$beforeCallbackCalled) {
            $beforeCallbackCalled = true;
        });

        $mutex->shouldReceive('create')->once()->with($event)->andReturn(false);

        $event->run($container);

        $this->assertTrue($event->skippedBecauseOverlapping);
        $this->assertFalse($event->executed);
        $this->assertFalse($beforeCallbackCalled);
    }

    public function testRunResetsSkippedBecauseOverlapping()
    {
        $container = new Container;
        $mutex = m::mock(EventMutex::class);
        $event = new class($mutex, 'php -i') extends Event
        {
            public $executions = 0;

            protected function execute($container)
            {
                $this->executions++;

                return 0;
            }
        };

        $event->withoutOverlapping();

        $mutex->shouldReceive('create')->twice()->with($event)->andReturn(false, true);
        $mutex->shouldReceive('forget')->once()->with($event);

        $event->run($container);
        $this->assertTrue($event->skippedBecauseOverlapping);

        $event->run($container);

        $this->assertFalse($event->skippedBecauseOverlapping);
        $this->assertSame(1, $event->executions);
    }

    public function testDaysOfMonthMethod()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $event->daysOfMonth(1, 15);
        $this->assertSame('0 0 1,15 * *', $event->getExpression());

        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->daysOfMonth([1, 10, 20, 30]);
        $this->assertSame('0 0 1,10,20,30 * *', $event->getExpression());
    }

    public function testEventDoesNotRunWhenPausedByDefault()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $this->assertFalse($event->runsWhenPaused());
    }

    public function testEventRunsWhenMarkedAsEvenWhenPaused()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->evenWhenPaused();

        $this->assertTrue($event->runsWhenPaused());
    }
}
