<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\ProcessUtils;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Mockery as m;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;

use function Illuminate\Support\php_binary;

class EventTest extends TestCase
{
    protected function tearDown(): void
    {
        Schedule::$outputShouldBeForwardedToConsole = false;

        parent::tearDown();
    }

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

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testBuildCommandWithUserUsingUnix()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->user('forge');

        $this->assertSame("sudo -u forge -- sh -c 'php -i > '\''/dev/null'\'' 2>&1'", $event->buildCommand());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testBuildCommandWithUserAndSpacesInOutputPathUsingUnix()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->user('forge')->sendOutputTo('/my folder/foo.log');

        $this->assertSame("sudo -u forge -- sh -c 'php -i > '\''/my folder/foo.log'\'' 2>&1'", $event->buildCommand());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testBuildCommandWithUserAndSingleQuotesInOutputPathUsingUnix()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->user('forge')->sendOutputTo("/tmp/o'brien.log");

        $this->assertSame("sudo -u forge -- sh -c 'php -i > '\''/tmp/o'\''\'\'''\''brien.log'\'' 2>&1'", $event->buildCommand());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testBuildCommandInBackgroundWithUserUsingUnix()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->user('forge')->runInBackground();

        $scheduleId = '"framework'.DIRECTORY_SEPARATOR.'schedule-eeb46c93d45e928d62aaf684d727e213b7094822"';

        $background = "(php -i > '/dev/null' 2>&1 ; '".php_binary()."' 'artisan' schedule:finish {$scheduleId} \"$?\") > '/dev/null' 2>&1 &";

        $this->assertSame('sudo -u forge -- sh -c '.ProcessUtils::escapeArgument($background), $event->buildCommand());
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

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testForwardOutputToConsoleSetsFlagAndReturnsEvent()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $result = $event->forwardOutputToConsole();

        $this->assertSame($event, $result);
        $this->assertTrue($event->outputShouldBeForwardedToConsole);
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testForwardOutputToConsoleDoesNotUseShellRedirectionOrTee()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->forwardOutputToConsole();

        $this->assertSame('php -i', $event->buildCommand());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testForwardOutputToConsolePreservesExitCode()
    {
        $event = new Event(m::mock(EventMutex::class), 'false');
        $event->forwardOutputToConsole();

        $this->assertSame('false', $event->buildCommand());
    }

    #[RequiresOperatingSystem('Windows')]
    public function testForwardOutputToConsoleDoesNotUseTeeOnWindows()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');
        $event->forwardOutputToConsole();

        $this->assertSame('php -i', $event->buildCommand());
    }

    public function testOutputIsNotForwardedByDefault()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $this->assertFalse($event->outputShouldBeForwardedToConsole);
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testScheduleWideForwardingAppliesRegardlessOfWhenEventWasCreated()
    {
        $before = new Event(m::mock(EventMutex::class), 'php -i');

        Schedule::$outputShouldBeForwardedToConsole = true;

        $after = new Event(m::mock(EventMutex::class), 'php -i');

        $this->assertFalse($before->outputShouldBeForwardedToConsole);
        $this->assertFalse($after->outputShouldBeForwardedToConsole);
        $this->assertSame('php -i', $before->buildCommand());
        $this->assertSame('php -i', $after->buildCommand());
    }

    public function testExecuteUsesForwardingCallbackWhenForwardingEnabled()
    {
        $event = $this->silentForwardingEvent('php -i');
        $event->forwardOutputToConsole();

        $callback = (new ReflectionMethod($event, 'outputForwardingCallback'))->invoke($event);

        $this->assertSame(2, (new ReflectionFunction($callback))->getNumberOfParameters());
    }

    public function testExecuteUsesNoopCallbackByDefault()
    {
        $event = new Event(m::mock(EventMutex::class), 'php -i');

        $callback = (new ReflectionMethod($event, 'outputForwardingCallback'))->invoke($event);

        $this->assertSame(0, (new ReflectionFunction($callback))->getNumberOfParameters());
    }

    public function testForwardedOutputIsStreamedToConfiguredOutputPath()
    {
        $path = tempnam(sys_get_temp_dir(), 'laravel-schedule-output-');

        $event = $this->silentForwardingEvent('php -i');
        $event->forwardOutputToConsole()->sendOutputTo($path);

        (new ReflectionMethod($event, 'openForwardedOutputHandle'))->invoke($event);

        $callback = (new ReflectionMethod($event, 'outputForwardingCallback'))->invoke($event);
        $callback('out', "hello world\n");

        (new ReflectionMethod($event, 'closeForwardedOutputHandle'))->invoke($event);

        $this->assertSame("hello world\n", file_get_contents($path));
        $this->assertSame([['out', "hello world\n"]], $event->forwardedLines);

        unlink($path);
    }

    public function testForwardedOutputIsAppendedWhenConfigured()
    {
        $path = tempnam(sys_get_temp_dir(), 'laravel-schedule-output-');
        file_put_contents($path, "existing\n");

        $event = $this->silentForwardingEvent('php -i');
        $event->forwardOutputToConsole()->appendOutputTo($path);

        (new ReflectionMethod($event, 'openForwardedOutputHandle'))->invoke($event);

        $callback = (new ReflectionMethod($event, 'outputForwardingCallback'))->invoke($event);
        $callback('out', "new line\n");

        (new ReflectionMethod($event, 'closeForwardedOutputHandle'))->invoke($event);

        $this->assertSame("existing\nnew line\n", file_get_contents($path));

        unlink($path);
    }

    public function testForwardedOutputIsNotStreamedForBackgroundEvents()
    {
        $path = tempnam(sys_get_temp_dir(), 'laravel-schedule-output-');
        file_put_contents($path, "existing\n");

        $event = $this->silentForwardingEvent('php -i');
        $event->forwardOutputToConsole()->appendOutputTo($path)->runInBackground();

        (new ReflectionMethod($event, 'openForwardedOutputHandle'))->invoke($event);

        $callback = (new ReflectionMethod($event, 'outputForwardingCallback'))->invoke($event);
        $this->assertSame(0, (new ReflectionFunction($callback))->getNumberOfParameters());
        $callback();

        (new ReflectionMethod($event, 'closeForwardedOutputHandle'))->invoke($event);

        $this->assertSame([], $event->forwardedLines);
        $this->assertSame("existing\n", file_get_contents($path));

        unlink($path);
    }

    public function testOpenForwardedOutputHandleThrowsForUnwritableDestination()
    {
        $event = $this->silentForwardingEvent('php -i');
        $event->forwardOutputToConsole()->sendOutputTo('/this/path/does/not/exist/output.log');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/\/this\/path\/does\/not\/exist\/output\.log/');

        (new ReflectionMethod($event, 'openForwardedOutputHandle'))->invoke($event);
    }

    public function testRunReportsFailureWhenOutputDestinationCannotBeOpened()
    {
        $mutex = m::mock(EventMutex::class);
        $mutex->shouldReceive('create')->andReturn(true);
        $mutex->shouldReceive('forget');

        $event = new Event($mutex, 'php -i');
        $event->forwardOutputToConsole()->sendOutputTo('/this/path/does/not/exist/output.log');

        $container = new Container;
        $container->instance(
            Repository::class,
            new Repository(new Dispatcher)
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/\/this\/path\/does\/not\/exist\/output\.log/');

        $event->run($container);
    }

    /**
     * Create an event whose console output is captured instead of being written to the real STDOUT/STDERR streams.
     *
     * @param  string  $command
     * @return \Illuminate\Console\Scheduling\Event
     */
    protected function silentForwardingEvent($command)
    {
        return new class(m::mock(EventMutex::class), $command) extends Event
        {
            public $forwardedLines = [];

            protected function forwardLineToConsole($type, $line)
            {
                $this->forwardedLines[] = [$type, $line];
            }
        };
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
