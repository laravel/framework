<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Scheduling\Schedulable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Tests\Console\Fixtures\CommandToTestWithSchedule;
use Illuminate\Tests\Console\Fixtures\JobToTestWithSchedule;
use Illuminate\Tests\Console\Fixtures\SchedulableCommand;
use Illuminate\Tests\Console\Fixtures\SchedulableJob;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schedule::class)]
final class ScheduleTest extends TestCase
{
    private Container $container;
    private EventMutex&MockInterface $eventMutex;
    private SchedulingMutex&MockInterface $schedulingMutex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container;
        Container::setInstance($this->container);
        $this->eventMutex = m::mock(EventMutex::class);
        $this->container->instance(EventMutex::class, $this->eventMutex);
        $this->schedulingMutex = m::mock(SchedulingMutex::class);
        $this->container->instance(SchedulingMutex::class, $this->schedulingMutex);
    }

    #[DataProvider('jobHonoursDisplayNameIfMethodExistsProvider')]
    public function testJobHonoursDisplayNameIfMethodExists(object $job, string $jobName): void
    {
        $schedule = new Schedule();
        $scheduledJob = $schedule->job($job);
        self::assertSame($jobName, $scheduledJob->description);
        self::assertFalse($this->container->resolved(JobToTestWithSchedule::class));
    }

    public static function jobHonoursDisplayNameIfMethodExistsProvider(): array
    {
        $job = new class implements ShouldQueue
        {
            public function displayName(): string
            {
                return 'testJob-123';
            }
        };

        return [
            [new JobToTestWithSchedule, JobToTestWithSchedule::class],
            [$job, 'testJob-123'],
        ];
    }

    public function testJobIsNotInstantiatedIfSuppliedAsClassname(): void
    {
        $schedule = new Schedule();
        $scheduledJob = $schedule->job(JobToTestWithSchedule::class);
        self::assertSame(JobToTestWithSchedule::class, $scheduledJob->description);
        self::assertFalse($this->container->resolved(JobToTestWithSchedule::class));
    }



    public function testNonSchedulableJob(): void
    {
        $schedule = new Schedule();
        $event = $schedule->job(new JobToTestWithSchedule);

        self::assertSame('* * * * *', $event->expression);
        self::assertFalse($event->withoutOverlapping);
    }

    public function testNonSchedulableCommand(): void
    {
        $schedule = new Schedule();
        $event = $schedule->command(CommandToTestWithSchedule::class);

        self::assertSame('* * * * *', $event->expression);
        self::assertFalse($event->withoutOverlapping);
    }



    public function testSchedulableJobInstance(): void
    {
        $schedule = new Schedule();
        $event = $schedule->job(new SchedulableJob);

        self::assertSame('0 10 * * *', $event->expression);
        self::assertTrue($event->withoutOverlapping);
    }

    public function testSchedulableJobClassName(): void
    {
        $schedule = new Schedule();
        $event = $schedule->job(SchedulableJob::class);

        self::assertSame('0 10 * * *', $event->expression);
        self::assertTrue($event->withoutOverlapping);
    }

    public function testSchedulableCommand(): void
    {
        $schedule = new Schedule();
        $event = $schedule->command(SchedulableCommand::class);

        self::assertSame('0 * * * *', $event->expression);
    }

    public function testSchedulableJobInGroups(): void
    {
        $schedule = new Schedule();

        $schedule->evenInMaintenanceMode()->group(function ($schedule) {
            $schedule->onOneServer()->group(function ($schedule) {
                $schedule->job(new SchedulableJob);
            });
        });

        $events = $schedule->events();

        self::assertCount(1, $events);
        self::assertTrue($events[0]->evenInMaintenanceMode);
        self::assertTrue($events[0]->onOneServer);
        self::assertSame('0 10 * * *', $events[0]->expression);
        self::assertTrue($events[0]->withoutOverlapping);
    }

    public function testSchedulableCommandInGroups(): void
    {
        $schedule = new Schedule();

        $schedule->evenInMaintenanceMode()->group(function ($schedule) {
            $schedule->onOneServer()->group(function ($schedule) {
                $schedule->command(SchedulableCommand::class);
            });
        });

        $events = $schedule->events();

        self::assertCount(1, $events);
        self::assertTrue($events[0]->evenInMaintenanceMode);
        self::assertTrue($events[0]->onOneServer);
        self::assertSame('0 * * * *', $events[0]->expression);
    }

    public function testSchedulableJobWithFluent(): void
    {
        $schedule = new Schedule();

        $event = $schedule->evenInMaintenanceMode()
            ->job(new SchedulableJob)
            ->weekdays();

        self::assertTrue($event->evenInMaintenanceMode);
        self::assertTrue($event->withoutOverlapping);
        self::assertSame('0 10 * * 1-5', $event->expression);
    }


    public function testSchedulableCommandWithFluent(): void
    {
        $schedule = new Schedule();

        $event = $schedule->evenInMaintenanceMode()
            ->command(SchedulableCommand::class)
            ->weekdays();

        self::assertTrue($event->evenInMaintenanceMode);
        self::assertTrue($event->withoutOverlapping);
        self::assertSame('0 * * * 1-5', $event->expression);
    }
}
