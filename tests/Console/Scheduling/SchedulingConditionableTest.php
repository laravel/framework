<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SchedulingConditionableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setup the Container and bind mocks for the Mutexes
        $container = Container::getInstance();
        $container->instance(EventMutex::class, m::mock(EventMutex::class));
        $container->instance(SchedulingMutex::class, m::mock(SchedulingMutex::class));
    }

    protected function tearDown(): void
    {
        m::close();
        Container::setInstance(null); // Clear the container
        parent::tearDown();
    }

    public function test_schedule_can_use_conditionable_when()
    {
        $schedule = new Schedule;

        $schedule->when(true, function ($s) {
            $s->command('test:true')->daily();
        });

        $schedule->when(false, function ($s) {
            $s->command('test:false')->daily();
        });

        $events = $schedule->events();

        $this->assertCount(1, $events);
        // Use contains instead of equals
        $this->assertStringContainsString('test:true', $events[0]->command);
    }

    public function test_schedule_can_use_conditionable_unless()
    {
        $schedule = new Schedule;

        $schedule->unless(true, function ($s) {
            $s->command('test:unless-true')->daily();
        });

        $schedule->unless(false, function ($s) {
            $s->command('test:unless-false')->daily();
        });

        $events = $schedule->events();

        $this->assertCount(1, $events);
        // Use contains instead of equals
        $this->assertStringContainsString('test:unless-false', $events[0]->command);
    }
}
