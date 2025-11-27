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

    public function testItCanFilterEventsByEnvironments(): void
    {
        $schedule = new Schedule();
        $schedule->job(JobToTestWithSchedule::class)->environments('production')->daily();
        $schedule->command('inspire')->environments(['staging', 'production'])->everyMinute();
        $schedule->command('foobar', ['a' => 'b'])->environments(['local', 'uat'])->everyMinute();
        $schedule->command('foobar')->hourly();

        $filteredEvents = $schedule->eventsForEnvironments(['production', 'staging']);

        $this->assertCount(3, $filteredEvents);

        $this->assertSame(JobToTestWithSchedule::class, $filteredEvents[0]->description);
        $this->assertSame(['production'], $filteredEvents[0]->environments);
        $this->assertSame('0 0 * * *', $filteredEvents[0]->expression);

        $this->assertStringEndsWith("'artisan' inspire", $filteredEvents[1]->command);
        $this->assertSame(['staging', 'production'], $filteredEvents[1]->environments);
        $this->assertSame('* * * * *', $filteredEvents[1]->expression);

        $this->assertStringEndsWith("'artisan' foobar", $filteredEvents[2]->command);
        $this->assertSame([], $filteredEvents[2]->environments);
        $this->assertSame('0 * * * *', $filteredEvents[2]->expression);
    }
}
