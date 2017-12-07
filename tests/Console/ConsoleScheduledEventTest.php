<?php

namespace Illuminate\Tests\Console;

use Mockery as m;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\Scheduling\Event;

class ConsoleScheduledEventTest extends TestCase
{
    /**
     * The default configuration timezone.
     *
     * @var string
     */
    protected $defaultTimezone;

    public function setUp()
    {
        $this->defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
    }

    public function tearDown()
    {
        date_default_timezone_set($this->defaultTimezone);
        Carbon::setTestNow(null);
        m::close();
    }

    public function testBasicCronCompilation()
    {
        $app = m::mock('Illuminate\Foundation\Application[isDownForMaintenance,environment]');
        $app->shouldReceive('isDownForMaintenance')->andReturn(false);
        $app->shouldReceive('environment')->andReturn('production');

        $event = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $this->assertEquals('* * * * * *', $event->getExpression());
        $this->assertTrue($event->isDue($app));
        $this->assertTrue($event->skip(function () {
            return true;
        })->isDue($app));
        $this->assertFalse($event->skip(function () {
            return true;
        })->filtersPass($app));

        $event = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $this->assertEquals('* * * * * *', $event->getExpression());
        $this->assertFalse($event->environments('local')->isDue($app));

        $event = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $this->assertEquals('* * * * * *', $event->getExpression());
        $this->assertFalse($event->when(function () {
            return false;
        })->filtersPass($app));

        $event = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $this->assertEquals('* * * * * *', $event->getExpression());
        $this->assertFalse($event->when(false)->filtersPass($app));

        // chained rules should be commutative
        $eventA = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $eventB = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $this->assertEquals(
            $eventA->daily()->hourly()->getExpression(),
            $eventB->hourly()->daily()->getExpression());

        $eventA = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $eventB = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $this->assertEquals(
            $eventA->weekdays()->hourly()->getExpression(),
            $eventB->hourly()->weekdays()->getExpression());
    }

    public function testEventIsDueCheck()
    {
        $app = m::mock('Illuminate\Foundation\Application[isDownForMaintenance,environment]');
        $app->shouldReceive('isDownForMaintenance')->andReturn(false);
        $app->shouldReceive('environment')->andReturn('production');
        Carbon::setTestNow(Carbon::create(2015, 1, 1, 0, 0, 0));

        $event = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $this->assertEquals('* * * * 4 *', $event->thursdays()->getExpression());
        $this->assertTrue($event->isDue($app));

        $event = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $this->assertEquals('0 19 * * 3 *', $event->wednesdays()->at('19:00')->timezone('EST')->getExpression());
        $this->assertTrue($event->isDue($app));
    }

    public function testTimeBetweenChecks()
    {
        $app = m::mock('Illuminate\Foundation\Application[isDownForMaintenance,environment]');
        $app->shouldReceive('isDownForMaintenance')->andReturn(false);
        $app->shouldReceive('environment')->andReturn('production');
        Carbon::setTestNow(Carbon::now()->startOfDay()->addHours(9));

        $event = new Event(m::mock('Illuminate\Console\Scheduling\Mutex'), 'php foo');
        $event->timezone('UTC');
        $this->assertTrue($event->between('8:00', '10:00')->filtersPass($app));
        $this->assertTrue($event->between('9:00', '9:00')->filtersPass($app));
        $this->assertFalse($event->between('10:00', '11:00')->filtersPass($app));

        $this->assertFalse($event->unlessBetween('8:00', '10:00')->filtersPass($app));
        $this->assertTrue($event->unlessBetween('10:00', '11:00')->isDue($app));
    }
}
