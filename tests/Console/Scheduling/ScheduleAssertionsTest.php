<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\CacheSchedulingMutex;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ScheduleAssertionsTest extends TestCase
{
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::getInstance();

        $container->instance(EventMutex::class, m::mock(CacheEventMutex::class));
        $container->instance(SchedulingMutex::class, m::mock(CacheSchedulingMutex::class));

        $this->schedule = new Schedule;
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        m::close();

        parent::tearDown();
    }

    public function testAssertScheduledMatchesCommandBySignature()
    {
        $this->schedule->command('foo:bar')->daily();

        $this->schedule->assertScheduled('foo:bar');
    }

    public function testAssertScheduledMatchesCommandByClass()
    {
        $this->schedule->command(ScheduleAssertionsTestCommand::class)->daily();

        $this->schedule->assertScheduled(ScheduleAssertionsTestCommand::class);
        $this->schedule->assertScheduled('foo:bar');
    }

    public function testAssertScheduledMatchesCommandWithParameters()
    {
        $this->schedule->command('foo:bar', ['--force'])->daily();

        $this->schedule->assertScheduled('foo:bar');
    }

    public function testAssertScheduledMatchesJobByClass()
    {
        $this->schedule->job(ScheduleAssertionsTestJob::class)->daily();

        $this->schedule->assertScheduled(ScheduleAssertionsTestJob::class);
    }

    public function testAssertScheduledMatchesGivenExpression()
    {
        $this->schedule->command('foo:bar')->dailyAt('03:00');

        $this->schedule->assertScheduled('foo:bar', '0 3 * * *');
    }

    public function testAssertScheduledFailsWhenExpressionDoesNotMatch()
    {
        $this->schedule->command('foo:bar')->hourly();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The [foo:bar] command was scheduled, but not with the [0 3 * * *] frequency. Found [0 * * * *].');

        $this->schedule->assertScheduled('foo:bar', '0 3 * * *');
    }

    public function testAssertScheduledFailsWhenCommandIsNotScheduled()
    {
        $this->schedule->command('foo:bar')->daily();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The expected [baz:qux] command was not scheduled. The following commands have been scheduled: [foo:bar].');

        $this->schedule->assertScheduled('baz:qux');
    }

    public function testAssertNotScheduled()
    {
        $this->schedule->command('foo:bar')->daily();

        $this->schedule->assertNotScheduled('baz:qux');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The unexpected [foo:bar] command was scheduled.');

        $this->schedule->assertNotScheduled('foo:bar');
    }

    public function testAssertNothingScheduled()
    {
        $this->schedule->assertNothingScheduled();

        $this->schedule->command('foo:bar')->daily();

        $this->expectException(AssertionFailedError::class);

        $this->schedule->assertNothingScheduled();
    }

    public function testSubMinuteEventsDoNotSatisfyTheEveryMinuteExpression()
    {
        $this->schedule->command('foo:bar')->everyTenSeconds();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The [foo:bar] command was scheduled, but not with the [* * * * *] frequency. Found [* * * * * every 10 seconds].');

        $this->schedule->assertScheduled('foo:bar', '* * * * *');
    }

    public function testUnnamedCallbackEventsAreListedWithinFailureMessages()
    {
        $this->schedule->call(fn () => null)->daily();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The expected [foo:bar] command was not scheduled. The following commands have been scheduled: [Callback].');

        $this->schedule->assertScheduled('foo:bar');
    }

    public function testUnnamedCallbackEventsDoNotMatchANamelessCommand()
    {
        $this->schedule->call(fn () => null)->daily();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine the name of the given command.');

        $this->schedule->assertScheduled(new SymfonyCommand);
    }
}

class ScheduleAssertionsTestCommand extends Command
{
    protected $signature = 'foo:bar {--force}';

    protected $description = 'A command used to test schedule assertions';
}

class ScheduleAssertionsTestJob
{
    //
}
