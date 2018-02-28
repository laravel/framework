<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\Schedulable;

class SchedulableClassEventTest extends TestCase
{
    // /*
    //  * @var \Illuminate\Console\Scheduling\Schedule
    //  */
    public $schedule;

    public function setUp()
    {
        parent::setUp();

        $container = Container::getInstance();

        $container->instance('Illuminate\Console\Scheduling\EventMutex', m::mock('Illuminate\Console\Scheduling\CacheEventMutex'));

        $container->instance('Illuminate\Console\Scheduling\SchedulingMutex', m::mock('Illuminate\Console\Scheduling\CacheSchedulingMutex'));

        $container->instance(
            'Illuminate\Console\Scheduling\Schedule', $this->schedule = new Schedule(m::mock('Illuminate\Console\Scheduling\EventMutex'))
        );
    }

    public function testCanAddSchedulableClass()
    {
        $this->schedule->use(SchedulableClassStub::class);

        $events = $this->schedule->events();

        $this->assertCount(1, $events);

        $this->assertEquals("Illuminate\Console\Scheduling\SchedulableClassEvent", get_class($events[0]));
    }

    public function testCannotAddNonSchedulableClass()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->schedule->use(NonSchedulableClassStub::class);

        $events = $this->schedule->events();

        $this->assertCount(0, $events);
    }

    public function testSchedulableClassEventHasEeryMinuteSchedule()
    {
        $this->schedule->use(SchedulableClassStub::class);

        $event = $this->schedule->events()[0];

        $this->assertEquals('* * * * *', $event->getExpression());
    }
}

class SchedulableClassStub
{
    use Schedulable;
}

class NonSchedulableClassStub
{
}
