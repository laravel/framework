<?php

namespace Illuminate\Tests\Console\Scheduling;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\Scheduling\Event;

class FrequencyTest extends TestCase
{
    /*
     * @var \Illuminate\Console\Scheduling\Event
     */
    protected $event;

    public function setUp()
    {
        $this->event = new Event(
            m::mock('Illuminate\Console\Scheduling\Mutex'),
            'php foo'
        );
    }

    public function testEveryMinute()
    {
        $this->assertEquals('* * * * * *', $this->event->getExpression());
        $this->assertEquals('* * * * * *', $this->event->everyMinute()->getExpression());
    }

    public function testEveryFiveMinutes()
    {
        $this->assertEquals('*/5 * * * * *', $this->event->everyFiveMinutes()->getExpression());
    }

    public function testDaily()
    {
        $this->assertEquals('0 0 * * * *', $this->event->daily()->getExpression());
    }

    public function testTwiceDaily()
    {
        $this->assertEquals('0 3,15 * * * *', $this->event->twiceDaily(3, 15)->getExpression());
    }

    public function testOverrideWithHourly()
    {
        $this->assertEquals('0 * * * * *', $this->event->everyFiveMinutes()->hourly()->getExpression());
        $this->assertEquals('37 * * * * *', $this->event->hourlyAt(37)->getExpression());
    }

    public function testMonthlyOn()
    {
        $this->assertEquals('0 15 4 * * *', $this->event->monthlyOn(4, '15:00')->getExpression());
    }

    public function testMonthlyOnWithMinutes()
    {
        $this->assertEquals('15 15 4 * * *', $this->event->monthlyOn(4, '15:15')->getExpression());
    }

    public function testWeekdaysDaily()
    {
        $this->assertEquals('0 0 * * 1-5 *', $this->event->weekdays()->daily()->getExpression());
    }

    public function testWeekdaysHourly()
    {
        $this->assertEquals('0 * * * 1-5 *', $this->event->weekdays()->hourly()->getExpression());
    }

    public function testWeekdays()
    {
        $this->assertEquals('* * * * 1-5 *', $this->event->weekdays()->getExpression());
    }

    public function testSundays()
    {
        $this->assertEquals('* * * * 0 *', $this->event->sundays()->getExpression());
    }

    public function testMondays()
    {
        $this->assertEquals('* * * * 1 *', $this->event->mondays()->getExpression());
    }

    public function testTuesdays()
    {
        $this->assertEquals('* * * * 2 *', $this->event->tuesdays()->getExpression());
    }

    public function testWednesdays()
    {
        $this->assertEquals('* * * * 3 *', $this->event->wednesdays()->getExpression());
    }

    public function testThursdays()
    {
        $this->assertEquals('* * * * 4 *', $this->event->thursdays()->getExpression());
    }

    public function testFridays()
    {
        $this->assertEquals('* * * * 5 *', $this->event->fridays()->getExpression());
    }

    public function testSaturdays()
    {
        $this->assertEquals('* * * * 6 *', $this->event->saturdays()->getExpression());
    }

    public function testFrequencyMacro()
    {
        Event::macro('everyXMinutes', function ($x) {
            return $this->spliceIntoPosition(1, "*/{$x}");
        });

        $this->assertEquals('*/6 * * * * *', $this->event->everyXMinutes(6)->getExpression());
    }
}
