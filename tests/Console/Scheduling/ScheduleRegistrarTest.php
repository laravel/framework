<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\Attributes\Scheduled;
use Illuminate\Console\Scheduling\DiscoveredScheduledTask;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleRegistrar;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use Mockery;
use PHPUnit\Framework\TestCase;

class ScheduleRegistrarTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_it_registers_discovered_tasks(): void
    {
        $container = new Container;

        Container::setInstance($container);

        $container->instance(
            EventMutex::class,
            Mockery::mock(EventMutex::class),
        );

        $container->instance(
            SchedulingMutex::class,
            Mockery::mock(SchedulingMutex::class),
        );

        $schedule = new Schedule;

        $task = new DiscoveredScheduledTask(
            class: TestScheduledService::class,
            method: 'cleanup',
            schedule: new Scheduled(
                frequency: 'daily',
                at: '03:00',
                withoutOverlapping: 30,
            ),
        );

        (new ScheduleRegistrar)->register(
            $schedule,
            [$task],
        );

        $events = $schedule->events();

        $this->assertCount(1, $events);
        $this->assertSame('0 3 * * *', $events[0]->expression);
        $this->assertSame(
            TestScheduledService::class.'@cleanup',
            $events[0]->description
        );
        $this->assertTrue($events[0]->withoutOverlapping);
        $this->assertSame(30, $events[0]->expiresAt);
    }
}

class TestScheduledService
{
    public function cleanup(): void
    {
        //
    }
}
