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

    #[DataProvider('scheduledEventAppendsOutputToDefaultOutputProvider')]
    public function testScheduledEventAppendsOutputToDefaultOutput(string $method, array $arguments): void
    {
        $schedule = new Schedule();

        /** @var \Illuminate\Console\Scheduling\Event $oldEvent */
        $oldEvent = call_user_func_array([$schedule, $method], $arguments);
        $oldEventOutput = $oldEvent->output;

        $schedule->setDefaultOutput($defaultOutput = '/custom/scheduler/output');

        /** @var \Illuminate\Console\Scheduling\Event $newEvent */
        $newEvent = call_user_func_array([$schedule, $method], $arguments);

        self::assertSame($oldEventOutput, $oldEvent->output);
        self::assertSame($defaultOutput, $newEvent->output);
        self::assertTrue($newEvent->shouldAppendOutput);
    }

    public static function scheduledEventAppendsOutputToDefaultOutputProvider(): array
    {
        return [
            ['job', [JobToTestWithSchedule::class]],
            ['command', ['env']],
            ['call', [fn () => 0]],
            ['exec', ['whoami']],
        ];
    }
}
