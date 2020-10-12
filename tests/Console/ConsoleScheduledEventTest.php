<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ConsoleScheduledEventTest extends TestCase
{
    /**
     * The default configuration timezone.
     *
     * @var string
     */
    protected $defaultTimezone;

    protected function setUp(): void
    {
        $this->defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->defaultTimezone);
        Carbon::setTestNow(null);
        m::close();
    }

    public function testBasicCronCompilation()
    {
        $app = m::mock(Application::class.'[isDownForMaintenance,environment]');
        $app->shouldReceive('isDownForMaintenance')->andReturn(false);
        $app->shouldReceive('environment')->andReturn('production');

        $event = new Event(m::mock(EventMutex::class), 'php foo');
        $this->assertSame('* * * * *', $event->getExpression());
        $this->assertTrue($event->isDue($app));
        $this->assertTrue($event->skip(function () {
            return true;
        })->isDue($app));
        $this->assertFalse($event->skip(function () {
            return true;
        })->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo');
        $this->assertSame('* * * * *', $event->getExpression());
        $this->assertFalse($event->environments('local')->isDue($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo');
        $this->assertSame('* * * * *', $event->getExpression());
        $this->assertFalse($event->when(function () {
            return false;
        })->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo');
        $this->assertSame('* * * * *', $event->getExpression());
        $this->assertFalse($event->when(false)->filtersPass($app));

        // chained rules should be commutative
        $eventA = new Event(m::mock(EventMutex::class), 'php foo');
        $eventB = new Event(m::mock(EventMutex::class), 'php foo');
        $this->assertEquals(
            $eventA->daily()->hourly()->getExpression(),
            $eventB->hourly()->daily()->getExpression());

        $eventA = new Event(m::mock(EventMutex::class), 'php foo');
        $eventB = new Event(m::mock(EventMutex::class), 'php foo');
        $this->assertEquals(
            $eventA->weekdays()->hourly()->getExpression(),
            $eventB->hourly()->weekdays()->getExpression());
    }

    public function testEventIsDueCheck()
    {
        $app = m::mock(Application::class.'[isDownForMaintenance,environment]');
        $app->shouldReceive('isDownForMaintenance')->andReturn(false);
        $app->shouldReceive('environment')->andReturn('production');
        Carbon::setTestNow(Carbon::create(2015, 1, 1, 0, 0, 0));

        $event = new Event(m::mock(EventMutex::class), 'php foo');
        $this->assertSame('* * * * 4', $event->thursdays()->getExpression());
        $this->assertTrue($event->isDue($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo');
        $this->assertSame('0 19 * * 3', $event->wednesdays()->at('19:00')->timezone('EST')->getExpression());
        $this->assertTrue($event->isDue($app));
    }

    public function testTimeBetweenChecks()
    {
        $app = m::mock(Application::class.'[isDownForMaintenance,environment]');
        $app->shouldReceive('isDownForMaintenance')->andReturn(false);
        $app->shouldReceive('environment')->andReturn('production');

        Carbon::setTestNow(Carbon::now()->startOfDay()->addHours(9));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertTrue($event->between('8:00', '10:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertTrue($event->between('9:00', '9:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertTrue($event->between('23:00', '10:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertTrue($event->between('8:00', '6:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertFalse($event->between('10:00', '11:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertFalse($event->between('10:00', '8:00')->filtersPass($app));
    }

    public function testTimeUnlessBetweenChecks()
    {
        $app = m::mock(Application::class.'[isDownForMaintenance,environment]');
        $app->shouldReceive('isDownForMaintenance')->andReturn(false);
        $app->shouldReceive('environment')->andReturn('production');

        Carbon::setTestNow(Carbon::now()->startOfDay()->addHours(9));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('8:00', '10:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('9:00', '9:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('23:00', '10:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertFalse($event->unlessBetween('8:00', '6:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertTrue($event->unlessBetween('10:00', '11:00')->filtersPass($app));

        $event = new Event(m::mock(EventMutex::class), 'php foo', 'UTC');
        $this->assertTrue($event->unlessBetween('10:00', '8:00')->filtersPass($app));
    }
}
