<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Schedulable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    public function testSchedulableJobInstanceConfiguresEvent(): void
    {
        $schedule = new Schedule();
        $event = $schedule->job(new SchedulableJob);

        self::assertSame('0 10 * * *', $event->expression);
        self::assertTrue($event->withoutOverlapping);
    }

    public function testSchedulableJobClassNameConfiguresEvent(): void
    {
        $schedule = new Schedule();
        $event = $schedule->job(SchedulableJob::class);

        self::assertSame('0 10 * * *', $event->expression);
        self::assertTrue($event->withoutOverlapping);
    }

    public function testSchedulableCommandConfiguresEvent(): void
    {
        $schedule = new Schedule();
        $event = $schedule->command(SchedulableCommand::class);

        self::assertSame('0 * * * *', $event->expression);
    }

    public function testNonSchedulableJobIsUnaffected(): void
    {
        $schedule = new Schedule();
        $event = $schedule->job(new JobToTestWithSchedule);

        self::assertSame('* * * * *', $event->expression);
        self::assertFalse($event->withoutOverlapping);
    }

    public function testSchedulableJobWorksWithinGroup(): void
    {
        $schedule = new Schedule();

        $this->eventMutex->shouldReceive('exists')->andReturn(false);

        $schedule->evenInMaintenanceMode()->group(function ($schedule) {
            $schedule->job(new SchedulableJob);
        });

        $events = $schedule->events();

        self::assertCount(1, $events);
        // Group attribute applied first, then Schedulable::schedule() configures the rest.
        self::assertTrue($events[0]->evenInMaintenanceMode);
        self::assertSame('0 10 * * *', $events[0]->expression);
        self::assertTrue($events[0]->withoutOverlapping);
    }

    public function testSchedulableJobWorksWithinNestedGroups(): void
    {
        $schedule = new Schedule();

        $this->eventMutex->shouldReceive('exists')->andReturn(false);

        // Outer group sets evenInMaintenanceMode, inner group sets onOneServer,
        // then Schedulable::schedule() sets daily()->at('10:00')->withoutOverlapping().
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

    public function testSchedulableJobWithPendingAttributesBefore(): void
    {
        $schedule = new Schedule();

        $this->eventMutex->shouldReceive('exists')->andReturn(false);

        // Pending attributes (evenInMaintenanceMode) are merged first,
        // then Schedulable::schedule() adds its own configuration on top.
        $event = $schedule->evenInMaintenanceMode()->job(new SchedulableJob);

        self::assertTrue($event->evenInMaintenanceMode);
        self::assertSame('0 10 * * *', $event->expression);
        self::assertTrue($event->withoutOverlapping);
    }

    public function testFluentChainingAfterSchedulableJobOverrides(): void
    {
        $schedule = new Schedule();

        $this->eventMutex->shouldReceive('exists')->andReturn(false);

        // Schedulable::schedule() sets daily()->at('10:00'), but fluent
        // chaining after the return can override the expression.
        $event = $schedule->job(new SchedulableJob)->weekdays();

        self::assertSame('0 10 * * 1-5', $event->expression);
        self::assertTrue($event->withoutOverlapping);
    }

    public function testSchedulableCommandWorksWithinGroup(): void
    {
        $schedule = new Schedule();

        $schedule->evenInMaintenanceMode()->group(function ($schedule) {
            $schedule->command(SchedulableCommand::class);
        });

        $events = $schedule->events();

        self::assertCount(1, $events);
        self::assertTrue($events[0]->evenInMaintenanceMode);
        self::assertSame('0 * * * *', $events[0]->expression);
    }

    public function testSchedulableCommandWorksWithinNestedGroups(): void
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

    public function testFluentChainingAfterSchedulableCommandOverrides(): void
    {
        $schedule = new Schedule();

        // Schedulable::schedule() sets hourly(), fluent chaining narrows to weekdays.
        $event = $schedule->command(SchedulableCommand::class)->weekdays();

        self::assertSame('0 * * * 1-5', $event->expression);
    }
}
