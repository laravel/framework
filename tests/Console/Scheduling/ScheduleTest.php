<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Tests\Console\Fixtures\JobToTestWithSchedule;
use Mockery as m;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schedule::class)]
final class ScheduleTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container;
        Container::setInstance($this->container);
        $eventMutex = m::mock(EventMutex::class);
        $this->container->instance(EventMutex::class, $eventMutex);
        $schedulingMutex = m::mock(SchedulingMutex::class);
        $this->container->instance(SchedulingMutex::class, $schedulingMutex);
    }

    #[DataProvider('jobHonoursDisplayNameIfMethodExistsProvider')]
    public function testJobHonoursDisplayNameIfMethodExists(object $job, string $jobName): void
    {
        $schedule = new Schedule();
        $scheduledJob = $schedule->job($job);
        $this->assertSame($jobName, $scheduledJob->description);
        $this->assertFalse($this->container->resolved(JobToTestWithSchedule::class));
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
        $this->assertSame(JobToTestWithSchedule::class, $scheduledJob->description);
        $this->assertFalse($this->container->resolved(JobToTestWithSchedule::class));
    }
}
